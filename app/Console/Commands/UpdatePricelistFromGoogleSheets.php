<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Template;
use App\Models\AutoReply;
use App\Models\Platform;

class UpdatePricelistFromGoogleSheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheets:update-pricelist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download pricelist from Google Sheet and update templates & auto-replies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = 'https://docs.google.com/spreadsheets/d/1iWHN_2zG7MJ6vPD3FPtZXMmqOhozXR1hF9SuOY2EU-w/export?format=xlsx';
        $tempPath = storage_path('app/pricelist_temp.xlsx');

        $this->info("Downloading pricelist Google Sheet...");
        $response = Http::timeout(60)->get($url);

        if (!$response->successful()) {
            $this->error("Failed to download Google Sheet. HTTP Status: " . $response->status());
            return 1;
        }

        file_put_contents($tempPath, $response->body());
        $this->info("Saved temporary Excel file to $tempPath");

        $this->info("Loading Excel file using PhpSpreadsheet...");
        try {
            $spreadsheet = IOFactory::load($tempPath);
        } catch (\Throwable $e) {
            $this->error("Failed to load Excel file: " . $e->getMessage());
            @unlink($tempPath);
            return 1;
        }

        $sheetNames = $spreadsheet->getSheetNames();
        $this->info("Found sheets: " . implode(', ', $sheetNames));

        $platform = Platform::where('module', 'whatsapp-web')->first();
        $platformId = $platform ? $platform->id : null;
        $ownerId = $platform ? $platform->owner_id : 1;

        $this->info("Using Owner ID: $ownerId | Platform ID: $platformId");

        // 1. Update Template ID 22 to be the Category List Menu
        $menuMeta = [
            "title" => "Daftar Harga Doorenz",
            "text" => "Halo kak! Silakan pilih kategori produk di bawah ini untuk melihat daftar harga lengkap kami secara langsung:\n\n*Catatan:* Harga di atas merupakan *harga estimasi saja (tidak mengikat)* dan dapat berubah sewaktu-waktu mengikuti harga bahan baku di pasar. Admin kami juga akan melakukan *pengecekan ketersediaan stok* terlebih dahulu sebelum pesanan Kakak diproses.",
            "footer" => "Dooren'z Percetakan Samarinda",
            "button_text" => "Pilih Kategori Produk",
            "sections" => [
                [
                    "title" => "Gelas & Papercup",
                    "rows" => [
                        [
                            "rowId" => "pricelist_gp_1warna",
                            "title" => "Gelas Plastik 1 Warna",
                            "description" => "Harga sablon gelas plastik, papercup, bowl 1 warna"
                        ],
                        [
                            "rowId" => "pricelist_gp_4warna",
                            "title" => "Gelas Plastik 4-6 Warna",
                            "description" => "Harga sablon gelas plastik full color 4-6 warna"
                        ]
                    ]
                ],
                [
                    "title" => "Kemasan Makanan",
                    "rows" => [
                        [
                            "rowId" => "pricelist_lunchbox",
                            "title" => "Lunchbox & Tray",
                            "description" => "Harga kemasan Lunch Box Kraft & Ivory, dan Tray"
                        ],
                        [
                            "rowId" => "pricelist_paperbag",
                            "title" => "Paperbag Makanan",
                            "description" => "Harga paperbag makanan putih & coklat"
                        ],
                        [
                            "rowId" => "pricelist_ivory",
                            "title" => "Box Ivory",
                            "description" => "Harga dus box bahan Ivory dengan sablon 1 warna"
                        ],
                        [
                            "rowId" => "pricelist_kraft",
                            "title" => "Box Kraft",
                            "description" => "Harga dus box bahan Kraft dengan sablon 1 warna"
                        ]
                    ]
                ],
                [
                    "title" => "Kertas, Plastik & Lainnya",
                    "rows" => [
                        [
                            "rowId" => "pricelist_plastik",
                            "title" => "Aneka Plastik & OPP",
                            "description" => "Harga plastik PE bawang, PP, OPP + sablon"
                        ],
                        [
                            "rowId" => "pricelist_kalender",
                            "title" => "Kalender Custom",
                            "description" => "Harga kalender dinding, meja, pocket custom"
                        ],
                        [
                            "rowId" => "pricelist_souvenir",
                            "title" => "Souvenir & Termos",
                            "description" => "Harga custom souvenir botol, gelas kaca, termos"
                        ],
                        [
                            "rowId" => "pricelist_payung",
                            "title" => "Payung Custom",
                            "description" => "Harga sablon payung lipat, standar, golf, premium"
                        ]
                    ]
                ]
            ]
        ];

        DB::table('templates')->where('id', 22)->update([
            'name' => 'Pricelist Doorenz (Menu Kategori)',
            'type' => 'list',
            'meta' => json_encode($menuMeta),
            'status' => 'active'
        ]);
        $this->info("Updated Template ID 22 to Category List Menu.");

        // Row mappings corresponding to sheet indexes (1-based sheet name / order)
        // Let's get sheet names and maps
        $rowMappings = [
            'GP 1 WARNA' => [
                'id' => 1,
                'name' => 'Pricelist: Gelas 1 Warna',
                'keywords' => ["pricelist_gp_1warna", "gelas plastik 1 warna"]
            ],
            'GP 4-6 WARNA' => [
                'id' => 2,
                'name' => 'Pricelist: Gelas 4-6 Warna',
                'keywords' => ["pricelist_gp_4warna", "gelas plastik 4-6 warna"]
            ],
            'LUNCHBOX & TRAY' => [
                'id' => 3,
                'name' => 'Pricelist: Lunchbox & Tray',
                'keywords' => ["pricelist_lunchbox", "lunchbox & tray"]
            ],
            'PAPERBAG MAKANAN' => [
                'id' => 4,
                'name' => 'Pricelist: Paperbag Makanan',
                'keywords' => ["pricelist_paperbag", "paperbag makanan"]
            ],
            'IVORY' => [
                'id' => 5,
                'name' => 'Pricelist: Box Ivory',
                'keywords' => ["pricelist_ivory", "box ivory"]
            ],
            'KRAFT' => [
                'id' => 6,
                'name' => 'Pricelist: Box Kraft',
                'keywords' => ["pricelist_kraft", "box kraft"]
            ],
            'PLASTIK' => [
                'id' => 7,
                'name' => 'Pricelist: Aneka Plastik & OPP',
                'keywords' => ["pricelist_plastik", "aneka plastik & opp"]
            ],
            'KALENDER' => [
                'id' => 8,
                'name' => 'Pricelist: Kalender Custom',
                'keywords' => ["pricelist_kalender", "kalender custom"]
            ],
            'SOUVENIR' => [
                'id' => 9,
                'name' => 'Pricelist: Souvenir & Termos',
                'keywords' => ["pricelist_souvenir", "souvenir & termos"]
            ],
            'PAYUNG' => [
                'id' => 10,
                'name' => 'Pricelist: Payung Custom',
                'keywords' => ["pricelist_payung", "payung custom"]
            ]
        ];

        $disclaimer = "\n\n⚠️ *Catatan:* Harga di atas merupakan *harga estimasi saja (tidak mengikat)* dan dapat berubah sewaktu-waktu mengikuti harga bahan baku di pasar. Admin kami juga akan melakukan *pengecekan ketersediaan stok* terlebih dahulu sebelum pesanan Kakak diproses.";

        foreach ($sheetNames as $sheetName) {
            $normalizedName = strtoupper(trim($sheetName));
            if (!isset($rowMappings[$normalizedName])) {
                $this->warn("Skipping sheet: $sheetName (No mapping found)");
                continue;
            }

            $mapping = $rowMappings[$normalizedName];
            $templateName = $mapping['name'];
            $keywords = $mapping['keywords'];

            $this->info("Processing sheet: $sheetName -> $templateName...");

            $sheet = $spreadsheet->getSheetByName($sheetName);
            $sheetRows = $sheet->toArray();

            // Format logic based on the sheet name
            $output = '';
            if ($normalizedName === 'GP 1 WARNA') {
                $output .= "🥤 *DAFTAR HARGA SABLON + GELAS PLASTIK 1 WARNA*\n\n";
                $currentCat = '';
                $notes = [];
                foreach ($sheetRows as $idx => $cols) {
                    if ($idx === 0 || $idx === 1) continue; // Skip title and headers

                    $cat = isset($cols[0]) ? trim($cols[0]) : '';
                    $ukuran = isset($cols[1]) ? trim($cols[1]) : '';
                    $produk = isset($cols[2]) ? trim($cols[2]) : '';
                    $harga = isset($cols[3]) ? trim($cols[3]) : '';
                    $moq = isset($cols[4]) ? trim($cols[4]) : '';

                    if (empty($ukuran) && empty($produk)) {
                        $note = trim($cat . ' ' . implode(' ', array_slice($cols, 1)));
                        $note = trim(preg_replace('/\s+/', ' ', $note));
                        if (!empty($note) && strtolower($note) !== 'note:') {
                            $notes[] = $note;
                        }
                        continue;
                    }

                    if ($cat !== $currentCat && !empty($cat)) {
                        $output .= "\n*Category: {$cat}*\n";
                        $currentCat = $cat;
                    }

                    if (!empty($produk)) {
                        $output .= "• {$ukuran} {$produk} : *Rp " . number_format((float)$harga, 0, ',', '.') . "* /pcs (Min. " . number_format((float)$moq, 0, ',', '.') . " pcs)\n";
                    }
                }
                if (!empty($notes)) {
                    $output .= "\n📝 *Catatan:*\n";
                    foreach ($notes as $n) {
                        $output .= "• {$n}\n";
                    }
                }
            }
            elseif ($normalizedName === 'GP 4-6 WARNA') {
                $output .= "🎨 *DAFTAR HARGA SABLON GELAS PLASTIK 4-6 WARNA*\n\n";
                $products = [];
                foreach ($sheetRows as $idx => $cols) {
                    if ($idx <= 1) continue; // Skip headers
                    $ukuran = isset($cols[0]) ? trim($cols[0]) : '';
                    $jenis = isset($cols[1]) ? trim($cols[1]) : '';
                    $qty = isset($cols[2]) ? trim($cols[2]) : '';
                    $harga = isset($cols[3]) ? trim($cols[3]) : '';

                    if (empty($ukuran) || empty($jenis) || (float)$harga == 0) continue;

                    $prodKey = "{$ukuran} ({$jenis})";
                    if (!isset($products[$prodKey])) {
                        $products[$prodKey] = [];
                    }
                    $products[$prodKey][] = [
                        'qty' => (float)$qty,
                        'harga' => (float)$harga
                    ];
                }

                foreach ($products as $prodName => $tiers) {
                    $output .= "*{$prodName}*:\n";
                    usort($tiers, function($a, $b) { return $a['qty'] <=> $b['qty']; });
                    foreach ($tiers as $t) {
                        $output .= "• Qty " . number_format($t['qty'], 0, ',', '.') . " pcs : *Rp " . number_format($t['harga'], 0, ',', '.') . "* /pcs\n";
                    }
                    $output .= "\n";
                }
            }
            elseif ($normalizedName === 'LUNCHBOX & TRAY') {
                $output .= "📦 *DAFTAR HARGA KEMASAN LUNCHBOX & TRAY*\n\n";
                foreach ($sheetRows as $idx => $cols) {
                    if ($idx === 0 || $idx === 1) continue; // Skip headers
                    $produk = isset($cols[0]) ? trim($cols[0]) : '';
                    $harga500 = isset($cols[1]) ? trim($cols[1]) : '';
                    
                    if (empty($produk)) continue;

                    if ((float)$harga500 > 0) {
                        $output .= "• *{$produk}* : *Rp " . number_format((float)$harga500, 0, ',', '.') . "* /pcs (MOQ 500 pcs)\n";
                    } else {
                        $output .= "• *{$produk}* : *Hubungi Admin*\n";
                    }
                }
            }
            elseif ($normalizedName === 'PAPERBAG MAKANAN') {
                $output .= "🛍️ *DAFTAR HARGA PAPERBAG MAKANAN*\n\n";
                foreach ($sheetRows as $idx => $cols) {
                    if ($idx === 0) continue;
                    $jenis = isset($cols[0]) ? trim($cols[0]) : '';
                    $harga = isset($cols[1]) ? trim($cols[1]) : '';
                    $total = isset($cols[2]) ? trim($cols[2]) : '';

                    if (empty($jenis)) continue;
                    $output .= "• *{$jenis}* : *Rp " . number_format((float)str_replace(['Rp', '.', ' '], '', $harga), 0, ',', '.') . "* /pcs (Total: {$total} per 1.000 pcs)\n";
                }
            }
            elseif ($normalizedName === 'IVORY') {
                $output .= "⬜ *DAFTAR HARGA BOX IVORY (SABLON 1 WARNA)*\n\n";
                $notes = [];
                foreach ($sheetRows as $idx => $cols) {
                    if ($idx === 0) continue;
                    $kode = isset($cols[0]) ? trim($cols[0]) : '';
                    $dimensi = isset($cols[1]) ? trim($cols[1]) : '';
                    $harga = isset($cols[2]) ? trim($cols[2]) : '';

                    if (empty($kode) || empty($dimensi) || strlen($kode) > 15 || stripos($kode, 'order') !== false) {
                        $possibleNote = implode(' ', array_filter($cols));
                        if (!empty($possibleNote)) $notes[] = trim($possibleNote);
                        continue;
                    }
                    if ((float)$harga > 0) {
                        $output .= "• *Kode {$kode}* ({$dimensi}) : *Rp " . number_format((float)$harga, 0, ',', '.') . "* /pcs\n";
                    } else {
                        $output .= "• *Kode {$kode}* ({$dimensi}) : *Hubungi Admin*\n";
                    }
                }
                if (!empty($notes)) {
                    $output .= "\n📝 *Catatan:*\n";
                    foreach ($notes as $note) {
                        $output .= "• {$note}\n";
                    }
                }
            }
            elseif ($normalizedName === 'KRAFT') {
                $output .= "🟫 *DAFTAR HARGA BOX KRAFT (SABLON 1 WARNA)*\n\n";
                $notes = [];
                foreach ($sheetRows as $idx => $cols) {
                    if ($idx === 0) continue;
                    $kode = isset($cols[0]) ? trim($cols[0]) : '';
                    $dimensi = isset($cols[1]) ? trim($cols[1]) : '';
                    $harga = isset($cols[2]) ? trim($cols[2]) : '';

                    if (empty($kode) || empty($dimensi) || strlen($kode) > 15 || stripos($kode, 'order') !== false) {
                        $possibleNote = implode(' ', array_filter($cols));
                        if (!empty($possibleNote)) $notes[] = trim($possibleNote);
                        continue;
                    }
                    if ((float)$harga > 0) {
                        $output .= "• *Kode {$kode}* ({$dimensi}) : *Rp " . number_format((float)$harga, 0, ',', '.') . "* /pcs\n";
                    } else {
                        $output .= "• *Kode {$kode}* ({$dimensi}) : *Hubungi Admin*\n";
                    }
                }
                if (!empty($notes)) {
                    $output .= "\n📝 *Catatan:*\n";
                    foreach ($notes as $note) {
                        $output .= "• {$note}\n";
                    }
                }
            }
            elseif ($normalizedName === 'PLASTIK') {
                $output .= "🟢 *PRICELIST ANEKA PLASTIK + SABLON MANUAL 1 WARNA*\n\n";
                $groups = [];
                $notes = [];
                foreach ($sheetRows as $idx => $cols) {
                    if ($idx <= 1) continue; // Skip title / headers
                    $jenis = isset($cols[1]) ? trim($cols[1]) : '';
                    $ukuran = isset($cols[2]) ? trim($cols[2]) : '';
                    $harga = isset($cols[6]) ? trim($cols[6]) : '';
                    $moq = isset($cols[7]) ? trim($cols[7]) : '';
                    $ket = isset($cols[8]) ? trim($cols[8]) : '';

                    if (empty($jenis)) {
                        $possibleNote = implode(' ', array_filter($cols));
                        if (!empty($possibleNote)) {
                            $notes[] = trim($possibleNote);
                        }
                        continue;
                    }

                    $cleanHarga = (float)str_replace(['Rp', '.', ' ', ','], '', $harga);
                    if ($cleanHarga > 0) {
                        if (!isset($groups[$jenis])) {
                            $groups[$jenis] = [];
                        }
                        $groups[$jenis][] = [
                            'ukuran' => $ukuran,
                            'harga' => $cleanHarga,
                            'moq' => $moq,
                            'ket' => $ket
                        ];
                    }
                }

                foreach ($groups as $jenisName => $items) {
                    $output .= "🛍️ *{$jenisName}*\n";
                    foreach ($items as $item) {
                        $moqStr = $item['moq'];
                        if (preg_match('/^(\d+)\s*(pcs|lembar)?$/i', $moqStr, $matches)) {
                            $moqStr = number_format((float)$matches[1], 0, ',', '.') . ' ' . ($matches[2] ?? 'pcs');
                        }
                        
                        $output .= "• Ukuran {$item['ukuran']}: *Rp " . number_format($item['harga'], 0, ',', '.') . "* /lembar (Min. {$moqStr})";
                        if (!empty($item['ket'])) {
                            $ketStr = str_ireplace('pack', 'pack', $item['ket']);
                            $ketStr = str_ireplace('isi', 'isi', $ketStr);
                            $output .= " | {$ketStr}";
                        }
                        $output .= "\n";
                    }
                    $output .= "\n";
                }

                if (!empty($notes)) {
                    $output .= "📝 *Keterangan:*\n";
                    foreach ($notes as $note) {
                        $output .= "• {$note}\n";
                    }
                }
            }
            elseif ($normalizedName === 'KALENDER') {
                $output .= "📅 *PRICELIST KALENDER CUSTOM*\n\n";
                foreach ($sheetRows as $idx => $cols) {
                    if ($idx === 0 || $idx === 1) continue; // Skip headers
                    $jenis = isset($cols[0]) ? trim($cols[0]) : '';
                    $bahan = isset($cols[1]) ? trim($cols[1]) : '';
                    $ukuran = isset($cols[2]) ? trim($cols[2]) : '';
                    $p50 = isset($cols[3]) ? trim($cols[3]) : '';
                    $p100 = isset($cols[4]) ? trim($cols[4]) : '';
                    $p250 = isset($cols[5]) ? trim($cols[5]) : '';
                    $p500 = isset($cols[6]) ? trim($cols[6]) : '';

                    if (empty($jenis) || stripos($jenis, 'jenis kalender') !== false) continue;

                    $output .= "• *{$jenis}* ({$bahan} - {$ukuran}):\n";
                    if (!empty($p50)) $output .= "  - 50 pcs : *{$p50}*\n";
                    if (!empty($p100)) $output .= "  - 100 pcs : *{$p100}*\n";
                    if (!empty($p250) && trim($p250) !== '-') $output .= "  - 250 pcs : *{$p250}*\n";
                    if (!empty($p500)) $output .= "  - 500 pcs : *{$p500}*\n";
                }
            }
            elseif ($normalizedName === 'SOUVENIR') {
                $output .= "🎁 *PENAWARAN SOUVENIR DOOREN’Z PERCETAKAN*\n\n";
                foreach ($sheetRows as $idx => $cols) {
                    if ($idx === 0 || $idx === 1) continue; // Skip headers
                    $nama = isset($cols[0]) ? trim($cols[0]) : '';
                    $harga = isset($cols[1]) ? trim($cols[1]) : '';

                    if (empty($nama) || stripos($nama, 'kategori') !== false || stripos($nama, 'harga') !== false) continue;

                    // If it is a catalog header or category section
                    if (strpos(strtoupper($nama), 'KATALOG') !== false && !filter_var($nama, FILTER_VALIDATE_URL) && stripos($nama, 'https://') === false) {
                        $output .= "\n*" . $this->cleanString($nama) . ":*\n";
                        continue;
                    }

                    if (empty($harga) || trim($harga) === '-') {
                        if (filter_var($nama, FILTER_VALIDATE_URL) || stripos($nama, 'https://') !== false) {
                            $output .= "👉 " . trim($nama) . "\n";
                        } else {
                            $output .= "• " . $this->cleanString($nama) . "\n";
                        }
                    } else {
                        $output .= "• " . $this->cleanString($nama) . " : *{$harga}*\n";
                    }
                }
            }
            elseif ($normalizedName === 'PAYUNG') {
                $output .= "🌂 *DAFTAR HARGA SABLON PAYUNG CUSTOM*\n\n";
                $currentCat = '';
                $notes = [];
                foreach ($sheetRows as $idx => $cols) {
                    if ($idx <= 1) continue; // Skip headers
                    $jenis = isset($cols[0]) ? trim($cols[0]) : '';
                    $nama = isset($cols[1]) ? trim($cols[1]) : '';
                    $harga = isset($cols[2]) ? trim($cols[2]) : '';
                    $ket = isset($cols[3]) ? trim($cols[3]) : '';

                    if (empty($nama) && empty($harga)) {
                        $noteText = trim($jenis);
                        if (!empty($noteText)) {
                            $notes[] = $noteText;
                        }
                        continue;
                    }

                    if (stripos($nama, 'nama') !== false || stripos($nama, 'warna') !== false) {
                        continue; // Skip table header row
                    }

                    if ($jenis !== $currentCat && !empty($jenis)) {
                        $output .= "\n*{$jenis}*:\n";
                        $currentCat = $jenis;
                    }

                    if (!empty($nama)) {
                        $output .= "• " . $this->cleanString($nama) . " : *{$harga}*";
                        if (!empty($ket)) {
                            $output .= " ({$ket})";
                        }
                        $output .= "\n";
                    }
                }
                if (!empty($notes)) {
                    $output .= "\n";
                    foreach ($notes as $n) {
                        if (stripos($n, 'ketentuan') !== false || stripos($n, 'tambahan') !== false) {
                            $output .= "\n*" . $this->cleanString($n) . ":*\n";
                        } else {
                            $output .= "• " . $this->cleanString($n) . "\n";
                        }
                    }
                }
            }

            // Save Template
            $content = trim($output) . $disclaimer;
            $meta = ['text' => $content];

            $template = Template::where('name', $templateName)->first();
            if ($template) {
                $templateId = $template->id;
                $template->update([
                    'meta' => $meta,
                    'type' => 'text',
                    'status' => 'active'
                ]);
                $this->info("Updated template ID: $templateId");
            } else {
                $templateId = DB::table('templates')->insertGetId([
                    'uuid' => uniqid(),
                    'module' => 'whatsapp',
                    'owner_id' => $ownerId,
                    'platform_id' => $platformId,
                    'name' => $templateName,
                    'type' => 'text',
                    'meta' => json_encode($meta),
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $this->info("Created template ID: $templateId");
            }

            // Check and update/create AutoReply mapping for this category
            $autoReply = AutoReply::where('template_id', $templateId)->first();
            $autoReplyData = [
                'owner_id' => $ownerId,
                'platform_id' => $platformId,
                'keywords' => $keywords,
                'message_type' => 'template',
                'template_id' => $templateId,
                'status' => 'active',
                'module' => 'whatsapp-web'
            ];

            if ($autoReply) {
                $autoReply->update($autoReplyData);
                $this->info("Updated auto reply ID: {$autoReply->id} for keywords: " . json_encode($keywords));
            } else {
                $newId = DB::table('auto_replies')->insertGetId(array_merge($autoReplyData, [
                    'keywords' => json_encode($keywords),
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
                $this->info("Created auto reply ID: $newId for keywords: " . json_encode($keywords));
            }
        }

        @unlink($tempPath);
        $this->info("Pricelist updated successfully!");
        return 0;
    }

    private function cleanString($str)
    {
        if (empty($str)) return '';
        // Remove leading spaces, colons, asterisks, dashes, bullet points, and common prefixes/emojis
        $str = preg_replace('/^[\s:*•\-✅🕒⚠️🖼️👉🥂💰📋]+/u', '', $str);
        // Remove trailing spaces, colons, asterisks
        $str = preg_replace('/[\s:*]+$/u', '', $str);
        return trim($str);
    }
}
