import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';

puppeteer.use(StealthPlugin());

// --- Konfigurasi ---
const MAX_RESULTS = 500; // Maksimal data yang diambil
const SCROLL_PAUSE_MS = 2000; // Jeda setiap scroll (ms)
const DETAIL_WAIT_MS = 1500; // Jeda buka detail bisnis (ms)
const MAX_SCROLL_STUCK = 5; // Berapa kali scroll tidak bergerak sebelum berhenti

/**
 * Tunggu elemen muncul di halaman dengan timeout
 */
async function waitForSelectorSafe(page, selector, timeout = 5000) {
    try {
        await page.waitForSelector(selector, { timeout });
        return true;
    } catch {
        return false;
    }
}

/**
 * Scroll feed sampai tidak ada item baru yang muncul (infinite scroll habis)
 * Return jumlah link yang berhasil dikumpulkan
 */
async function scrollAndCollectLinks(page) {
    console.error('Scrolling feed untuk mengumpulkan semua listing...');

    const FEED_SELECTOR = '[role="feed"]';
    const feedExists = await waitForSelectorSafe(page, FEED_SELECTOR, 15000);

    if (!feedExists) {
        console.error('Feed tidak ditemukan. Mencoba fallback selector...');
        const fallback = await waitForSelectorSafe(page, '.m6QErb', 10000);
        if (!fallback) {
            console.error('Semua feed selector gagal.');
            return [];
        }
    }

    let collectedLinks = new Set();
    let stuckCount = 0;
    let prevCount = 0;

    while (collectedLinks.size < MAX_RESULTS) {
        // Kumpulkan semua link artikel yang sudah muncul
        const links = await page.evaluate(() => {
            const articles = document.querySelectorAll('[role="feed"] [role="article"] a[href*="/maps/place/"]');
            return Array.from(articles).map(a => a.href).filter(Boolean);
        });

        links.forEach(l => collectedLinks.add(l));

        // Cek apakah sudah selesai (ada teks "Anda telah mencapai akhir daftar")
        const isEnd = await page.evaluate(() => {
            const endMarkers = [
                "You've reached the end of the list",
                "Anda telah mencapai akhir daftar",
                "hasil telah habis",
                "No more results"
            ];
            const bodyText = document.body.innerText;
            return endMarkers.some(m => bodyText.includes(m));
        });

        if (isEnd) {
            console.error(`Feed habis. Total link terkumpul: ${collectedLinks.size}`);
            break;
        }

        // Scroll ke bawah feed
        await page.evaluate(() => {
            const feed = document.querySelector('[role="feed"]');
            if (feed) feed.scrollBy(0, 5000);
        });

        await new Promise(r => setTimeout(r, SCROLL_PAUSE_MS));

        // Deteksi stuck (tidak ada item baru)
        if (collectedLinks.size === prevCount) {
            stuckCount++;
            console.error(`Scroll stuck (${stuckCount}/${MAX_SCROLL_STUCK}). Links: ${collectedLinks.size}`);
            if (stuckCount >= MAX_SCROLL_STUCK) {
                console.error('Scroll berhenti - tidak ada item baru.');
                break;
            }
            // Extra scroll paksa
            await page.evaluate(() => {
                const feed = document.querySelector('[role="feed"]');
                if (feed) feed.scrollTop = feed.scrollHeight;
            });
            await new Promise(r => setTimeout(r, SCROLL_PAUSE_MS));
        } else {
            stuckCount = 0;
            console.error(`Link terkumpul: ${collectedLinks.size}`);
        }

        prevCount = collectedLinks.size;
    }

    return Array.from(collectedLinks).slice(0, MAX_RESULTS);
}

/**
 * Buka detail setiap bisnis dan ambil semua data
 */
async function scrapeDetailPage(page, url) {
    try {
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await new Promise(r => setTimeout(r, DETAIL_WAIT_MS));

        const data = await page.evaluate(() => {
            // Nama bisnis
            const name = document.querySelector('h1.DUwDvf, h1[data-section-id="0x0"]')?.innerText?.trim()
                || document.querySelector('h1')?.innerText?.trim()
                || null;

            // Alamat
            const addressEl = document.querySelector('button[data-item-id="address"]');
            const address = addressEl?.innerText?.trim() || null;

            // Nomor telepon - coba berbagai selector
            const phoneSelectors = [
                'button[data-tooltip="Salin nomor telepon"]',
                'button[data-item-id*="phone"]',
                'a[href^="tel:"]',
                'button[aria-label*="phone"]',
                'button[aria-label*="telepon"]',
                'span[aria-label*="Phone"]'
            ];
            let phone = null;
            for (const sel of phoneSelectors) {
                const el = document.querySelector(sel);
                if (el) {
                    phone = el.innerText?.trim() || el.getAttribute('aria-label')?.replace(/[^0-9+\-() ]/g, '').trim() || null;
                    if (phone) break;
                }
            }
            // Fallback: cari dari href tel:
            if (!phone) {
                const telLink = document.querySelector('a[href^="tel:"]');
                if (telLink) phone = telLink.href.replace('tel:', '').trim();
            }

            // Website
            const websiteEl = document.querySelector('a[data-item-id="authority"]');
            const website = websiteEl?.href || null;

            // Rating
            const ratingEl = document.querySelector('div.F7nice span[aria-hidden="true"], span.MW4etd');
            const rating = ratingEl?.innerText?.trim() || null;

            // Jumlah review
            const reviewEl = document.querySelector('span.UY7F9');
            const reviews = reviewEl?.innerText?.replace(/[^0-9]/g, '') || null;

            // Kategori bisnis
            const categoryEl = document.querySelector('button.DkEaL');
            const category = categoryEl?.innerText?.trim() || null;

            // Jam buka
            const hoursEl = document.querySelector('div[data-hide-tooltip] span.ZDu9vd');
            const hours = hoursEl?.innerText?.trim() || null;

            return { name, address, phone, website, rating, reviews, category, hours };
        });

        // Ambil email dari website jika ada
        let email = null;
        if (data.website) {
            email = await tryExtractEmailFromWebsite(page, data.website);
        }

        return { ...data, email, maps_url: url };

    } catch (err) {
        console.error(`Gagal scrape detail ${url}: ${err.message}`);
        return null;
    }
}

/**
 * Coba buka website bisnis dan ambil email yang tercantum
 */
async function tryExtractEmailFromWebsite(page, websiteUrl) {
    try {
        // Buka di tab yang sama, timeout singkat
        const response = await page.goto(websiteUrl, {
            waitUntil: 'domcontentloaded',
            timeout: 10000
        });

        if (!response || !response.ok()) return null;

        const email = await page.evaluate(() => {
            // Cari semua mailto: link
            const mailtoLinks = document.querySelectorAll('a[href^="mailto:"]');
            if (mailtoLinks.length > 0) {
                return mailtoLinks[0].href.replace('mailto:', '').split('?')[0].trim();
            }
            // Fallback: cari pattern email di teks halaman
            const text = document.body.innerText;
            const emailRegex = /[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/g;
            const matches = text.match(emailRegex);
            if (matches) {
                // Filter out common false positives
                const filtered = matches.filter(e =>
                    !e.includes('example.') &&
                    !e.includes('sentry.') &&
                    !e.includes('@2x') &&
                    !e.endsWith('.png') &&
                    !e.endsWith('.jpg')
                );
                return filtered[0] || null;
            }
            return null;
        });

        return email;
    } catch {
        return null;
    }
}

/**
 * Fungsi utama: Scrape Google Maps berdasarkan query
 */
async function scrapeGoogleMaps(query) {
    const browser = await puppeteer.launch({
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-blink-features=AutomationControlled',
            '--disable-infobars',
            '--window-size=1280,900',
            '--lang=id-ID,id'
        ]
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 1280, height: 900 });

        // Set User-Agent agar terlihat seperti browser biasa
        await page.setUserAgent(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        );

        // Buka Google Maps dengan query
        const searchUrl = `https://www.google.com/maps/search/${encodeURIComponent(query)}`;
        console.error(`Membuka: ${searchUrl}`);
        await page.goto(searchUrl, { waitUntil: 'networkidle2', timeout: 30000 });

        // Tutup dialog consent jika muncul
        try {
            const consentBtn = await page.$('button[aria-label="Terima semua"], button[aria-label="Accept all"]');
            if (consentBtn) {
                await consentBtn.click();
                await new Promise(r => setTimeout(r, 1000));
            }
        } catch { /* skip */ }

        // Kumpulkan semua link dari feed (infinite scroll sampai habis)
        const placeLinks = await scrollAndCollectLinks(page);
        console.error(`\nTotal link bisnis dikumpulkan: ${placeLinks.length}`);

        if (placeLinks.length === 0) {
            console.error('Tidak ada link ditemukan.');
            return [];
        }

        // Scrape detail setiap bisnis
        const results = [];
        for (let i = 0; i < placeLinks.length; i++) {
            const url = placeLinks[i];
            console.error(`[${i + 1}/${placeLinks.length}] Scraping: ${url.substring(0, 80)}...`);

            const detail = await scrapeDetailPage(page, url);
            if (detail && detail.name) {
                results.push(detail);
            }

            // Jeda acak antara 1-3 detik untuk menghindari deteksi bot
            const delay = 1000 + Math.random() * 2000;
            await new Promise(r => setTimeout(r, delay));
        }

        console.error(`\nSelesai! Berhasil scrape ${results.length} bisnis.`);
        return results;

    } catch (error) {
        console.error('Fatal scraping error:', error.message);
        return [];
    } finally {
        await browser.close();
    }
}

// --- Entry Point ---
const query = process.argv[2];
if (!query) {
    console.error('Error: Tidak ada query yang diberikan.');
    console.error('Penggunaan: node scraper.js "Apotek Bandung"');
    process.exit(1);
}

scrapeGoogleMaps(query).then(results => {
    // Output JSON bersih ke stdout (diambil oleh PHP)
    process.stdout.write(JSON.stringify(results));
    process.exit(0);
}).catch(err => {
    console.error('Uncaught error:', err);
    process.exit(1);
});
