<?php

namespace Modules\Whatsapp\App\Services;

/**
 * SpamWordFilter — Filter kata-kata yang berisiko menyebabkan ban WhatsApp.
 *
 * Kata-kata ini sering terdeteksi sebagai SPAM oleh sistem WhatsApp.
 * Filter akan mengganti kata tersebut dengan padanan yang lebih aman.
 */
class SpamWordFilter
{
    /**
     * Daftar kata spam → pengganti yang aman.
     * Key   = kata/frasa asli (case-insensitive)
     * Value = pengganti aman. Null = hapus kata tersebut.
     */
    protected static array $replacements = [
        // === KATA PROMOSI LANGSUNG ===
        'gratis'         => 'free',
        'free'           => 'tanpa biaya',
        'diskon'         => 'harga spesial',
        'promo'          => 'penawaran',
        'sale'           => 'penawaran terbatas',
        'murah'          => 'terjangkau',
        'termurah'       => 'terbaik',
        'terbaik'        => 'unggulan',
        'garansi'        => 'jaminan',

        // === KATA MENANG / HADIAH ===
        'selamat anda menang'  => 'terima kasih atas partisipasi anda',
        'anda menang'          => 'anda terpilih',
        'menang'               => 'berhasil',
        'hadiah'               => 'apresiasi',
        'bonus'                => 'tambahan',
        'jackpot'              => null,
        'lucky'                => null,
        'pemenang'             => 'peserta terpilih',

        // === LINK BERBAHAYA ===
        'bit.ly'         => '',
        'tinyurl.com'    => '',
        'shorturl.at'    => '',
        'rb.gy'          => '',
        'cutt.ly'        => '',
        't.me'           => '',
        'ow.ly'          => '',

        // === KATA AJAKAN KLIK ===
        'klik di sini'   => 'buka tautan berikut',
        'klik disini'    => 'buka tautan berikut',
        'klik link'      => 'buka tautan',
        'klik sekarang'  => 'hubungi kami',
        'click here'     => 'open the link',
        'click now'      => 'contact us',
        'tap here'       => 'open below',

        // === KATA URGENCY BERLEBIHAN ===
        'segera'         => 'silakan',
        'buruan'         => 'ayo',
        'terbatas'       => 'tersedia',
        'stok terbatas'  => 'stok tersedia',
        'jangan sampai ketinggalan' => 'jangan lewatkan kesempatan ini',
        'limited'        => 'special',
        'last chance'    => 'special offer',
        'expire'         => 'berakhir',

        // === KATA KEUANGAN BERISIKO ===
        'pinjaman'       => 'pembiayaan',
        'kredit'         => 'cicilan',
        'hutang'         => null,
        'investasi'      => 'program',
        'profit'         => 'keuntungan',
        'passive income' => 'penghasilan tambahan',
        'trading'        => 'program',
        'saham'          => null,
        'forex'          => null,
        'crypto'         => null,
        'bitcoin'        => null,

        // === KATA DEWASA / TIDAK SENONOH ===
        'judi'           => null,
        'togel'          => null,
        'kasino'         => null,
        'slot'           => null,
        'betting'        => null,
        'taruhan'        => null,
    ];

    /**
     * Filter pesan — ganti kata spam dengan padanannya.
     *
     * @param  string  $text  Teks pesan asli
     * @return string         Teks pesan setelah difilter
     */
    public static function filter(string $text): string
    {
        foreach (self::$replacements as $spamWord => $replacement) {
            $pattern = '/\b' . preg_quote($spamWord, '/') . '\b/iu';

            if ($replacement === null) {
                // Hapus kata ini
                $text = preg_replace($pattern, '', $text);
            } else {
                // Ganti dengan kata aman
                $text = preg_replace_callback($pattern, function ($matches) use ($replacement) {
                    // Pertahankan huruf kapital jika kata asli diawali kapital
                    if (ctype_upper(substr($matches[0], 0, 1))) {
                        return ucfirst($replacement);
                    }
                    return $replacement;
                }, $text);
            }
        }

        // Bersihkan spasi ganda akibat penghapusan kata
        $text = preg_replace('/\s{2,}/', ' ', $text);

        return trim($text);
    }

    /**
     * Deteksi apakah teks mengandung kata spam (tanpa mengganti).
     *
     * @param  string  $text
     * @return array   Daftar kata spam yang ditemukan
     */
    public static function detect(string $text): array
    {
        $found = [];
        foreach (self::$replacements as $spamWord => $replacement) {
            $pattern = '/\b' . preg_quote($spamWord, '/') . '\b/iu';
            if (preg_match($pattern, $text)) {
                $found[] = $spamWord;
            }
        }
        return $found;
    }
}
