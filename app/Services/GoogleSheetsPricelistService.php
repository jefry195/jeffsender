<?php

namespace App\Services;

use App\Models\AutoReply;
use App\Models\Template;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * GoogleSheetsPricelistService
 *
 * Downloads pricelist data from a Google Sheet (exported as XLSX),
 * parses each sheet tab, and updates the corresponding Template and
 * AutoReply records in the database.
 *
 * Usage:
 *   $service = new GoogleSheetsPricelistService($ownerId, $platformId);
 *   $result  = $service->sync($spreadsheetUrl);
 */
class GoogleSheetsPricelistService
{
    /** @var array<string, array{name:string, keywords:string[]}> */
    protected array $sheetMappings = [
        'GP 1 WARNA' => [
            'name'     => 'Pricelist: Gelas 1 Warna',
            'keywords' => ['pricelist_gp_1warna', 'gelas plastik 1 warna'],
        ],
        'GP 4-6 WARNA' => [
            'name'     => 'Pricelist: Gelas 4-6 Warna',
            'keywords' => ['pricelist_gp_4warna', 'gelas plastik 4-6 warna'],
        ],
        'LUNCHBOX & TRAY' => [
            'name'     => 'Pricelist: Lunchbox & Tray',
            'keywords' => ['pricelist_lunchbox', 'lunchbox & tray'],
        ],
        'PAPERBAG MAKANAN' => [
            'name'     => 'Pricelist: Paperbag Makanan',
            'keywords' => ['pricelist_paperbag', 'paperbag makanan'],
        ],
        'IVORY' => [
            'name'     => 'Pricelist: Box Ivory',
            'keywords' => ['pricelist_ivory', 'box ivory'],
        ],
        'KRAFT' => [
            'name'     => 'Pricelist: Box Kraft',
            'keywords' => ['pricelist_kraft', 'box kraft'],
        ],
        'PLASTIK' => [
            'name'     => 'Pricelist: Aneka Plastik & OPP',
            'keywords' => ['pricelist_plastik', 'aneka plastik & opp'],
        ],
        'KALENDER' => [
            'name'     => 'Pricelist: Kalender Custom',
            'keywords' => ['pricelist_kalender', 'kalender custom'],
        ],
        'SOUVENIR' => [
            'name'     => "Pricelist: Souvenir & Termos",
            'keywords' => ['pricelist_souvenir', 'souvenir & termos'],
        ],
        'PAYUNG' => [
            'name'     => 'Pricelist: Payung Custom',
            'keywords' => ['pricelist_payung', 'payung custom'],
        ],
    ];

    protected string $disclaimer = "\n\n⚠️ *Catatan:* Harga di atas merupakan *harga estimasi saja (tidak mengikat)* dan dapat berubah sewaktu-waktu mengikuti harga bahan baku di pasar. Admin kami juga akan melakukan *pengecekan ketersediaan stok* terlebih dahulu sebelum pesanan Kakak diproses.";

    public function __construct(
        protected int $ownerId,
        protected ?int $platformId = null
    ) {}

    /**
     * Main entry point: download the sheet from $spreadsheetUrl and sync.
     *
     * @param  string $spreadsheetUrl  Full Google Sheets export URL or base spreadsheet URL
     * @return array{updated:int, created:int, skipped:int, errors:array}
     */
    public function sync(string $spreadsheetUrl): array
    {
        // Normalise URL to always export as xlsx
        $exportUrl = $this->toExportUrl($spreadsheetUrl);

        Log::info('GoogleSheetsPricelistService: Downloading sheet', ['url' => $exportUrl]);

        $tempPath = storage_path('app/pricelist_temp_' . uniqid() . '.xlsx');

        try {
            $response = Http::timeout(60)->get($exportUrl);

            if (! $response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()} saat download Google Sheet.");
            }

            file_put_contents($tempPath, $response->body());
            $spreadsheet = IOFactory::load($tempPath);
        } catch (\Throwable $e) {
            @unlink($tempPath);
            throw $e;
        }

        $result = ['updated' => 0, 'created' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $key = strtoupper(trim($sheetName));
            if (! isset($this->sheetMappings[$key])) {
                $result['skipped']++;
                continue;
            }

            try {
                $sheet    = $spreadsheet->getSheetByName($sheetName);
                $rows     = $sheet->toArray();
                $mapping  = $this->sheetMappings[$key];
                $content  = $this->parseSheet($key, $rows);

                if (empty(trim($content))) {
                    $result['skipped']++;
                    continue;
                }

                $textContent = trim($content) . $this->disclaimer;
                $this->upsertTemplate($mapping, $textContent, $result);

                if ($key === 'GP 1 WARNA') {
                    $this->updateSubTemplates($rows, $result);
                }
            } catch (\Throwable $e) {
                $result['errors'][] = "Sheet [{$sheetName}]: " . $e->getMessage();
                Log::error('GoogleSheetsPricelistService: Error parsing sheet', [
                    'sheet' => $sheetName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        @unlink($tempPath);

        Log::info('GoogleSheetsPricelistService: Sync done', $result);

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    //  Sheet parsers
    // ─────────────────────────────────────────────────────────────

    protected function parseSheet(string $key, array $rows): string
    {
        return match ($key) {
            'GP 1 WARNA'      => $this->parseGp1Warna($rows),
            'GP 4-6 WARNA'    => $this->parseGp4Warna($rows),
            'LUNCHBOX & TRAY' => $this->parseLunchbox($rows),
            'PAPERBAG MAKANAN'=> $this->parsePaperbag($rows),
            'IVORY'           => $this->parseIvoryKraft($rows, 'ivory'),
            'KRAFT'           => $this->parseIvoryKraft($rows, 'kraft'),
            'PLASTIK'         => $this->parsePlastik($rows),
            'KALENDER'        => $this->parseKalender($rows),
            'SOUVENIR'        => $this->parseSouvenir($rows),
            'PAYUNG'          => $this->parsePayung($rows),
            default           => '',
        };
    }

    protected function parseGp1Warna(array $rows): string
    {
        $out = "🥤 *DAFTAR HARGA SABLON + GELAS PLASTIK 1 WARNA*\n\n";
        $currentCat = '';
        $notes = [];

        foreach ($rows as $idx => $cols) {
            if ($idx <= 1) continue; // skip title & header

            $cat    = trim($cols[0] ?? '');
            $ukuran = trim($cols[1] ?? '');
            $produk = trim($cols[2] ?? '');
            $harga  = trim($cols[3] ?? '');
            $moq    = trim($cols[4] ?? '');

            if (empty($ukuran) && empty($produk)) {
                $note = trim($cat . ' ' . implode(' ', array_slice($cols, 1)));
                $note = trim(preg_replace('/\s+/', ' ', $note));
                if (!empty($note) && strtolower($note) !== 'note:') {
                    $notes[] = $note;
                }
                continue;
            }

            if ($cat !== $currentCat && !empty($cat)) {
                $out .= "\n*{$cat}*\n";
                $currentCat = $cat;
            }

            $cleanHarga = $this->cleanPrice($harga);
            $cleanMoq = $this->cleanPrice($moq);
            if (!empty($produk) && $cleanHarga > 0) {
                $out .= "• {$ukuran} {$produk} : *Rp " . number_format($cleanHarga, 0, ',', '.') . "* /pcs (Min. " . number_format($cleanMoq, 0, ',', '.') . " pcs)\n";
            }
        }

        if (!empty($notes)) {
            $out .= "\n📝 *Catatan:*\n";
            foreach ($notes as $n) {
                $out .= "• {$n}\n";
            }
        }

        return $out;
    }

    protected function parseGp4Warna(array $rows): string
    {
        $out = "🎨 *DAFTAR HARGA SABLON GELAS PLASTIK 4-6 WARNA*\n\n";
        $products = [];

        foreach ($rows as $idx => $cols) {
            if ($idx <= 1) continue;
            $ukuran = trim($cols[0] ?? '');
            $jenis  = trim($cols[1] ?? '');
            $qty    = trim($cols[2] ?? '');
            $harga  = trim($cols[3] ?? '');

            $cleanHarga = $this->cleanPrice($harga);
            $cleanQty = $this->cleanPrice($qty);
            if (empty($ukuran) || empty($jenis) || $cleanHarga == 0) continue;

            $key = "{$ukuran} ({$jenis})";
            $products[$key][] = ['qty' => $cleanQty, 'harga' => $cleanHarga];
        }

        foreach ($products as $name => $tiers) {
            $out .= "*{$name}*:\n";
            usort($tiers, fn($a, $b) => $a['qty'] <=> $b['qty']);
            foreach ($tiers as $t) {
                $out .= "• Qty " . number_format($t['qty'], 0, ',', '.') . " pcs : *Rp " . number_format($t['harga'], 0, ',', '.') . "* /pcs\n";
            }
            $out .= "\n";
        }

        return $out;
    }

    protected function parseLunchbox(array $rows): string
    {
        $out = "📦 *DAFTAR HARGA KEMASAN LUNCHBOX & TRAY*\n\n";

        foreach ($rows as $idx => $cols) {
            if ($idx <= 1) continue;
            $produk  = trim($cols[0] ?? '');
            $harga500 = trim($cols[1] ?? '');
            if (empty($produk)) continue;

            $cleanHarga = $this->cleanPrice($harga500);
            if ($cleanHarga > 0) {
                $out .= "• *{$produk}* : *Rp " . number_format($cleanHarga, 0, ',', '.') . "* /pcs (MOQ 500 pcs)\n";
            } else {
                $out .= "• *{$produk}* : *Hubungi Admin*\n";
            }
        }

        return $out;
    }

    protected function parsePaperbag(array $rows): string
    {
        $out = "🛍️ *DAFTAR HARGA PAPERBAG MAKANAN*\n\n";

        foreach ($rows as $idx => $cols) {
            if ($idx === 0) continue;
            $jenis = trim($cols[0] ?? '');
            $harga = trim($cols[1] ?? '');
            $total = trim($cols[2] ?? '');
            if (empty($jenis)) continue;

            $cleanHarga = $this->cleanPrice($harga);
            $out .= "• *{$jenis}* : *Rp " . number_format($cleanHarga, 0, ',', '.') . "* /pcs (Total: {$total} per 1.000 pcs)\n";
        }

        return $out;
    }

    protected function parseIvoryKraft(array $rows, string $type): string
    {
        $emoji  = $type === 'ivory' ? '⬜' : '🟫';
        $label  = $type === 'ivory' ? 'IVORY (SABLON 1 WARNA)' : 'KRAFT (SABLON 1 WARNA)';
        $out    = "{$emoji} *DAFTAR HARGA BOX {$label}*\n\n";
        $notes  = [];

        foreach ($rows as $idx => $cols) {
            if ($idx === 0) continue;
            $kode   = trim($cols[0] ?? '');
            $dimensi= trim($cols[1] ?? '');
            $harga  = trim($cols[2] ?? '');

            if (empty($kode) || empty($dimensi) || strlen($kode) > 15 || stripos($kode, 'order') !== false) {
                $possibleNote = implode(' ', array_filter($cols));
                if (!empty($possibleNote)) $notes[] = trim($possibleNote);
                continue;
            }

            $cleanHarga = $this->cleanPrice($harga);
            if ($cleanHarga > 0) {
                $out .= "• *Kode {$kode}* ({$dimensi}) : *Rp " . number_format($cleanHarga, 0, ',', '.') . "* /pcs\n";
            } else {
                $out .= "• *Kode {$kode}* ({$dimensi}) : *Hubungi Admin*\n";
            }
        }

        if (!empty($notes)) {
            $out .= "\n📝 *Catatan:*\n";
            foreach ($notes as $note) {
                $out .= "• {$note}\n";
            }
        }

        return $out;
    }

    protected function parsePlastik(array $rows): string
    {
        $out    = "🟢 *PRICELIST ANEKA PLASTIK + SABLON MANUAL 1 WARNA*\n\n";
        $groups = [];
        $notes  = [];

        foreach ($rows as $idx => $cols) {
            if ($idx <= 1) continue;
            $jenis = trim($cols[1] ?? '');
            $ukuran= trim($cols[2] ?? '');
            $harga = trim($cols[6] ?? '');
            $moq   = trim($cols[7] ?? '');
            $ket   = trim($cols[8] ?? '');

            if (empty($jenis)) {
                $possibleNote = implode(' ', array_filter($cols));
                if (!empty($possibleNote)) $notes[] = trim($possibleNote);
                continue;
            }

            $cleanHarga = $this->cleanPrice($harga);
            if ($cleanHarga > 0) {
                $groups[$jenis][] = ['ukuran' => $ukuran, 'harga' => $cleanHarga, 'moq' => $moq, 'ket' => $ket];
            }
        }

        foreach ($groups as $jenisName => $items) {
            $out .= "🛍️ *{$jenisName}*\n";
            foreach ($items as $item) {
                $moqStr = $item['moq'];
                if (preg_match('/^(\d+)\s*(pcs|lembar)?$/i', $moqStr, $m)) {
                    $moqStr = number_format((float)$m[1], 0, ',', '.') . ' ' . ($m[2] ?? 'pcs');
                }
                $out .= "• Ukuran {$item['ukuran']}: *Rp " . number_format($item['harga'], 0, ',', '.') . "* /lembar (Min. {$moqStr})";
                if (!empty($item['ket'])) $out .= " | {$item['ket']}";
                $out .= "\n";
            }
            $out .= "\n";
        }

        if (!empty($notes)) {
            $out .= "📝 *Keterangan:*\n";
            foreach ($notes as $note) {
                $out .= "• {$note}\n";
            }
        }

        return $out;
    }

    protected function parseKalender(array $rows): string
    {
        $out = "📅 *PRICELIST KALENDER CUSTOM*\n\n";

        foreach ($rows as $idx => $cols) {
            if ($idx <= 1) continue;
            $jenis  = trim($cols[0] ?? '');
            $bahan  = trim($cols[1] ?? '');
            $ukuran = trim($cols[2] ?? '');
            $p50    = trim($cols[3] ?? '');
            $p100   = trim($cols[4] ?? '');
            $p250   = trim($cols[5] ?? '');
            $p500   = trim($cols[6] ?? '');

            if (empty($jenis) || stripos($jenis, 'jenis kalender') !== false) continue;

            $out .= "• *{$jenis}* ({$bahan} - {$ukuran}):\n";
            if (!empty($p50))  $out .= "  - 50 pcs : *{$p50}*\n";
            if (!empty($p100)) $out .= "  - 100 pcs : *{$p100}*\n";
            if (!empty($p250) && trim($p250) !== '-') $out .= "  - 250 pcs : *{$p250}*\n";
            if (!empty($p500)) $out .= "  - 500 pcs : *{$p500}*\n";
            $out .= "\n";
        }

        return $out;
    }

    protected function parseSouvenir(array $rows): string
    {
        $out = "🎁 *PENAWARAN SOUVENIR DOOREN'Z PERCETAKAN*\n\n";

        foreach ($rows as $idx => $cols) {
            if ($idx <= 1) continue;
            $nama  = trim($cols[0] ?? '');
            $harga = trim($cols[1] ?? '');

            if (empty($nama) || stripos($nama, 'kategori') !== false || stripos($nama, 'harga') !== false) continue;

            if (stripos($nama, 'KATALOG') !== false && stripos($nama, 'https://') === false) {
                $out .= "\n*" . $this->cleanString($nama) . ":*\n";
                continue;
            }

            if (empty($harga) || trim($harga) === '-') {
                if (stripos($nama, 'https://') !== false) {
                    $out .= "👉 " . trim($nama) . "\n";
                } else {
                    $out .= "• " . $this->cleanString($nama) . "\n";
                }
            } else {
                $out .= "• " . $this->cleanString($nama) . " : *{$harga}*\n";
            }
        }

        return $out;
    }

    protected function parsePayung(array $rows): string
    {
        $out = "🌂 *DAFTAR HARGA SABLON PAYUNG CUSTOM*\n\n";
        $currentCat = '';
        $notes = [];

        foreach ($rows as $idx => $cols) {
            if ($idx <= 1) continue;
            $jenis = trim($cols[0] ?? '');
            $nama  = trim($cols[1] ?? '');
            $harga = trim($cols[2] ?? '');
            $ket   = trim($cols[3] ?? '');

            if (empty($nama) && empty($harga)) {
                if (!empty($jenis)) $notes[] = $jenis;
                continue;
            }

            if (stripos($nama, 'nama') !== false || stripos($nama, 'warna') !== false) continue;

            if ($jenis !== $currentCat && !empty($jenis)) {
                $out .= "\n*{$jenis}*:\n";
                $currentCat = $jenis;
            }

            if (!empty($nama)) {
                $out .= "• " . $this->cleanString($nama) . " : *{$harga}*";
                if (!empty($ket)) $out .= " ({$ket})";
                $out .= "\n";
            }
        }

        if (!empty($notes)) {
            $out .= "\n";
            foreach ($notes as $n) {
                if (stripos($n, 'ketentuan') !== false || stripos($n, 'tambahan') !== false) {
                    $out .= "\n*" . $this->cleanString($n) . ":*\n";
                } else {
                    $out .= "• " . $this->cleanString($n) . "\n";
                }
            }
        }

        return $out;
    }

    // ─────────────────────────────────────────────────────────────
    //  DB upsert
    // ─────────────────────────────────────────────────────────────

    protected function upsertTemplate(array $mapping, string $content, array &$result): void
    {
        $name     = $mapping['name'];
        $keywords = $mapping['keywords'];
        // Template model has 'meta' => 'array' cast, so pass array directly (NOT json_encode)
        $metaArray = ['text' => $content];

        // Try to find existing template by name
        $template = Template::where('name', $name)->first();

        if ($template) {
            $template->update(['meta' => $metaArray, 'type' => 'text', 'status' => 'active']);
            $templateId = $template->id;
            $result['updated']++;
        } else {
            // For new inserts via DB::table, we need to json_encode manually
            $templateId = DB::table('templates')->insertGetId([
                'uuid'        => (string)\Illuminate\Support\Str::uuid(),
                'module'      => 'whatsapp-web',
                'owner_id'    => $this->ownerId,
                'platform_id' => $this->platformId,
                'name'        => $name,
                'type'        => 'text',
                'meta'        => json_encode($metaArray),
                'status'      => 'active',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $result['created']++;
        }

        // Upsert AutoReply mapping
        $existing = AutoReply::where('template_id', $templateId)->first();
        $data = [
            'owner_id'     => $this->ownerId,
            'platform_id'  => $this->platformId,
            'keywords'     => $keywords,
            'message_type' => 'template',
            'template_id'  => $templateId,
            'status'       => 'active',
            'module'       => 'whatsapp-web',
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            DB::table('auto_replies')->insert(array_merge($data, [
                'keywords'   => json_encode($keywords),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }


    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    protected function toExportUrl(string $url): string
    {
        $exportUrl = $url;
        // Extract spreadsheet ID and convert to export URL
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            $exportUrl = "https://docs.google.com/spreadsheets/d/{$m[1]}/export?format=xlsx";
        } elseif (!str_contains($url, 'format=xlsx') && !str_contains($url, 'export?')) {
            $exportUrl = $url . (str_contains($url, '?') ? '&' : '?') . 'format=xlsx';
        }

        // Append timestamp to bypass Google's export cache
        $exportUrl .= (str_contains($exportUrl, '?') ? '&' : '?') . 't=' . time();

        return $exportUrl;
    }

    protected function cleanPrice(?string $priceStr): float
    {
        if ($priceStr === null) return 0.0;
        $clean = preg_replace('/[^\d]/', '', $priceStr);
        return empty($clean) ? 0.0 : (float)$clean;
    }

    protected function cleanString(string $str): string
    {
        $str = preg_replace('/^[\s:*•\-✅🕒⚠️🖼️👉🥂💰📋]+/u', '', $str);
        $str = preg_replace('/[\s:*]+$/u', '', $str);
        return trim($str);
    }

    protected function updateSubTemplates(array $rows, array &$result): void
    {
        $gelasPlastik = [];
        $papercup = [];
        $paperbowl = [];
        $cupSealer = [];

        foreach ($rows as $idx => $cols) {
            if ($idx <= 1) continue; // skip title & header

            $kategori = trim($cols[0] ?? '');
            $ukuran   = trim($cols[1] ?? '');
            $produk   = trim($cols[2] ?? '');
            $harga    = trim($cols[3] ?? '');
            $moq      = trim($cols[4] ?? '');

            $cleanHarga = $this->cleanPrice($harga);
            $cleanMoq = $this->cleanPrice($moq);
            if (empty($ukuran) || empty($produk) || $cleanHarga <= 0) {
                continue;
            }

            $item = [
                'ukuran' => $ukuran,
                'produk' => $produk,
                'harga'  => $cleanHarga,
                'moq'    => $cleanMoq
            ];

            $catLower = strtolower($kategori);
            if (str_contains($catLower, 'gelas plastik')) {
                $gelasPlastik[$ukuran][] = $item;
            } elseif (str_contains($catLower, 'papercup') || str_contains($catLower, 'paper cup')) {
                $papercup[$ukuran][] = $item;
            } elseif (str_contains($catLower, 'paperbowl') || str_contains($catLower, 'paper bowl')) {
                $paperbowl[] = $item;
            } elseif (str_contains($catLower, 'cup sealer') || str_contains($catLower, 'sealer')) {
                $cupSealer[] = $item;
            }
        }

        // 1. Gelas Plastik sub-templates
        foreach ($gelasPlastik as $ukuran => $items) {
            $ukuranNoSpace = str_replace(' ', '', $ukuran);
            $templateName = "Pricelist: Gelas Plastik 1W - {$ukuranNoSpace}";
            $ukuranUpper = strtoupper($ukuran);

            $content = "🥤 *HARGA GELAS PLASTIK {$ukuranUpper} – SABLON 1 WARNA*\n\n";
            foreach ($items as $it) {
                $content .= "• {$it['ukuran']} {$it['produk']} : Rp " . number_format($it['harga'], 0, ',', '.') . "/pcs (Min. " . number_format($it['moq'], 0, ',', '.') . " pcs)\n";
            }
            $content .= $this->disclaimer;

            $mapping = [
                'name' => $templateName,
                'keywords' => ["pricelist_gp1_gp_{$ukuranNoSpace}", "gelas plastik {$ukuranNoSpace} 1 warna"]
            ];
            $this->upsertTemplate($mapping, $content, $result);
        }

        // 2. Papercup sub-templates
        foreach ($papercup as $ukuran => $items) {
            $ukuranNoSpace = str_replace(' ', '', $ukuran);
            $templateName = "Pricelist: Papercup 1W - {$ukuranNoSpace}";
            $ukuranUpper = strtoupper($ukuran);

            $content = "☕ *HARGA PAPERCUP {$ukuranUpper} – SABLON 1 WARNA*\n\n";
            foreach ($items as $it) {
                $content .= "• {$it['ukuran']} {$it['produk']} : Rp " . number_format($it['harga'], 0, ',', '.') . "/pcs (Min. " . number_format($it['moq'], 0, ',', '.') . " pcs)\n";
            }
            $content .= $this->disclaimer;

            $mapping = [
                'name' => $templateName,
                'keywords' => ["pricelist_gp1_pc_{$ukuranNoSpace}", "papercup {$ukuranNoSpace} 1 warna"]
            ];
            $this->upsertTemplate($mapping, $content, $result);
        }

        // 3. Paperbowl sub-template
        if (!empty($paperbowl)) {
            $templateName = "Pricelist: Paperbowl 1W";
            $content = "🥣 *HARGA PAPERBOWL – SABLON 1 WARNA*\n\n";
            foreach ($paperbowl as $it) {
                $content .= "• {$it['ukuran']} {$it['produk']} : Rp " . number_format($it['harga'], 0, ',', '.') . "/pcs (Min. " . number_format($it['moq'], 0, ',', '.') . " pcs)\n";
            }
            $content .= $this->disclaimer;

            $mapping = [
                'name' => $templateName,
                'keywords' => ["pricelist_gp1_paperbowl", "paperbowl 1 warna"]
            ];
            $this->upsertTemplate($mapping, $content, $result);
        }

        // 4. Cup Sealer sub-template
        if (!empty($cupSealer)) {
            $templateName = "Pricelist: Cup Sealer 1W";
            $content = "🔒 *HARGA CUP SEALER – SABLON 1 WARNA*\n\n";
            foreach ($cupSealer as $it) {
                $content .= "• {$it['ukuran']} {$it['produk']} : Rp " . number_format($it['harga'], 0, ',', '.') . "/roll (Min. " . number_format($it['moq'], 0, ',', '.') . " pcs)\n";
            }
            $content .= $this->disclaimer;

            $mapping = [
                'name' => $templateName,
                'keywords' => ["pricelist_gp1_cupsealer", "cup sealer 1 warna"]
            ];
            $this->upsertTemplate($mapping, $content, $result);
        }
    }
}
