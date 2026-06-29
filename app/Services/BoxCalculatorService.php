<?php

namespace App\Services;

class BoxCalculatorService
{
    public static function getProductTypes(bool $isDoorenz = false): array
    {
        $types = [
            'lunchBox' => 'Kotak Lunch Box',
            'riceBox' => 'Rice Box',
            'dineIn' => 'Dine In (Nampan Kertas)',
            'kotakTutupTerpisah' => 'Kotak Tutup Terpisah',
            'kotakSambung' => 'Mailer (Kotak Sambung)',
            'straightTuckEnd' => 'Straight Tuck End (STE)',
            'kebab' => 'Kemasan Kebab',
            'kotakMug' => 'Kotak Mug',
            'burger' => 'Kotak Burger',
        ];
        if ($isDoorenz) {
            $types['customFlat'] = 'Custom Ukuran Datar';
        }
        return $types;
    }

    public static function getBahanOptions(bool $isDoorenz = false): array
    {
        $options = [
            // Offset
            'kraft290_off' => 'Kraft 290gr (Offset)',
            'ivory250_off' => 'Ivory 250gr (Offset)',
            'ivory300_off' => 'Ivory 300gr (Offset)',
            'ivory350_off' => 'Ivory 350gr (Offset)',
            'duplex250_off' => 'Duplex 250gr (Offset)',
            'duplex310_off' => 'Duplex 310gr (Offset)',
            'ap120_off' => 'Art Paper 120gr (Offset)',
            'ap150_off' => 'Art Paper 150gr (Offset)',
            'ap210_off' => 'Art Paper 210gr (Offset)',
            'ap230_off' => 'Art Paper 230gr (Offset)',
            'ap260_off' => 'Art Paper 260gr (Offset)',
            'ap310_off' => 'Art Paper 310gr (Offset)',
        ];

        if ($isDoorenz) {
            // Digital
            $options['fotopaper_dig'] = 'Fotopaper (Digital)';
            $options['basetik_dig'] = 'Basetik (Digital)';
            $options['ap120_dig'] = 'Art Paper 120gr (Digital)';
            $options['ap150_dig'] = 'Art Paper 150gr (Digital)';
            $options['ap210_dig'] = 'Art Paper 210gr (Digital)';
            $options['ap230_dig'] = 'Art Paper 230gr (Digital)';
            $options['ap260_dig'] = 'Art Paper 260gr (Digital)';
            $options['ap310_dig'] = 'Art Paper 310gr (Digital)';
            $options['hvs80_dig'] = 'HVS 80gr (Digital)';
            $options['hvs100_dig'] = 'HVS 100gr (Digital)';
            $options['stiker_hvs_dig'] = 'Stiker HVS (Digital)';
            $options['stiker_bontax_dig'] = 'Stiker Bontax (Digital)';
            $options['stiker_quantac_dig'] = 'Stiker Quantac (Digital)';
            $options['stiker_chromo_dig'] = 'Stiker Chromo (Digital)';
            $options['stiker_vinyl_dig'] = 'Stiker Vinyl (Digital)';
        }

        return $options;
    }

    public static function getLaminasiOptions(): array
    {
        return [
            'none' => 'Tanpa Laminasi',
            'glossy' => 'Laminasi Glossy',
            'doff' => 'Laminasi Doff',
        ];
    }

    public static function getPlanoSizes(): array
    {
        return [
            'plano_79_109' => ['name' => 'Plano (79 x 109 cm)', 'w' => 109.0, 'h' => 79.0, 'factor' => 1],
            'plano_65_100' => ['name' => 'Plano (65 x 100 cm)', 'w' => 100.0, 'h' => 65.0, 'factor' => 1],
            'plano_90_120' => ['name' => 'Plano (90 x 120 cm)', 'w' => 120.0, 'h' => 90.0, 'factor' => 1],
            'a3plus' => ['name' => 'A3+ (32.9 x 48.3 cm)', 'w' => 48.3, 'h' => 32.9, 'factor' => 1],
        ];
    }

    public static function getHargaBahanOffset(): array
    {
        return [
            'kraft290_off' => [
                'plano_79_109' => 4500,
                'plano_65_100' => 3375,
                'plano_90_120' => 4750,
            ],
            'ivory250_off' => [
                'plano_79_109' => 4500,
                'plano_65_100' => 3375,
            ],
            'ivory300_off' => [
                'plano_79_109' => 4750,
                'plano_65_100' => 3563,
            ],
            'ivory350_off' => [
                'plano_79_109' => 5250,
                'plano_65_100' => 3938,
            ],
            'duplex250_off' => [
                'plano_79_109' => 4500,
                'plano_65_100' => 3375,
            ],
            'duplex310_off' => [
                'plano_79_109' => 5500,
                'plano_65_100' => 4125,
            ],
            'ap120_off' => [
                'plano_79_109' => 3500,
                'plano_65_100' => 2625,
            ],
            'ap150_off' => [
                'plano_79_109' => 4500,
                'plano_65_100' => 3375,
            ],
            'ap210_off' => [
                'plano_79_109' => 5250,
                'plano_65_100' => 3938,
            ],
            'ap230_off' => [
                'plano_79_109' => 6000,
                'plano_65_100' => 4500,
            ],
            'ap260_off' => [
                'plano_79_109' => 6750,
                'plano_65_100' => 5063,
            ],
            'ap310_off' => [
                'plano_79_109' => 7500,
                'plano_65_100' => 5625,
            ],
        ];
    }

    public static function getHargaBahanDigital(): array
    {
        return [
            'ap310_dig' => 4000,
            'ap260_dig' => 3300,
            'stiker_chromo_dig' => 4000,
            'stiker_vinyl_dig' => 6000,
            
            'fotopaper_dig' => 5000,
            'basetik_dig' => 3800,
            'ap230_dig' => 3100,
            'ap210_dig' => 2900,
            'ap150_dig' => 2700,
            'ap120_dig' => 2500,
            'hvs100_dig' => 2300,
            'hvs80_dig' => 2000,
            'stiker_bontax_dig' => 4000,
            'stiker_hvs_dig' => 3500,
            'stiker_quantac_dig' => 8000,
        ];
    }

    public static function calculateFlatSize(string $type, array $v, string $bahan = '', bool $isDoorenz = false): array
    {
        // Default bleed = 0.75 cm
        $bleed = 0.75;
        
        if ($isDoorenz && !empty($bahan)) {
            $bahanLower = strtolower($bahan);
            $isDigital = (strpos($bahanLower, '_dig') !== false || strpos($bahanLower, 'dtf_') !== false);
            
            if ($isDigital) {
                if (strpos($bahanLower, 'stiker') !== false || strpos($bahanLower, 'sticker') !== false || strpos($bahanLower, 'dtf') !== false) {
                    $bleed = 0.3;
                } else {
                    $bleed = 0.1;
                }
            } else {
                // Offset bleed is 0.75 cm
                $bleed = 0.75;
            }
        }
        
        switch ($type) {
            case 'lunchBox':
            case 'riceBox':
                $p_atas = (float)($v['p_atas'] ?? 0);
                $l_atas = (float)($v['l_atas'] ?? 0);
                $p_bawah = (float)($v['p_bawah'] ?? 0);
                $l_bawah = (float)($v['l_bawah'] ?? 0);
                $t = (float)($v['t'] ?? 0);
                $tutup = (float)($v['tutup'] ?? 0);
                
                $w = $l_atas + $l_bawah + (2 * $t) + $tutup;
                $h = $p_bawah + (2 * $t);
                break;
                
            case 'dineIn':
                $p = (float)($v['p'] ?? 0);
                $l = (float)($v['l'] ?? 0);
                $t = (float)($v['t'] ?? 0);
                
                $w = $p + (2 * $t);
                $h = $l + (2 * $t);
                break;
                
            case 'kotakTutupTerpisah':
                // For separate lid box, calculate Bawah and Atas
                $p_bawah = (float)($v['p_bawah'] ?? 0);
                $l_bawah = (float)($v['l_bawah'] ?? 0);
                $t_bawah = (float)($v['t_bawah'] ?? 0);
                $p_atas = (float)($v['p_atas'] ?? 0);
                $l_atas = (float)($v['l_atas'] ?? 0);
                $t_atas = (float)($v['t_atas'] ?? 0);
                
                $wB = $p_bawah + (2 * $t_bawah);
                $hB = $l_bawah + (2 * $t_bawah);
                
                $wA = $p_atas + (2 * $t_atas);
                $hA = $l_atas + (2 * $t_atas);
                
                // Return both parts
                return [
                    'bawah' => ['w' => $wB + 2 * $bleed, 'h' => $hB + 2 * $bleed],
                    'atas' => ['w' => $wA + 2 * $bleed, 'h' => $hA + 2 * $bleed],
                ];
                
            case 'kotakSambung':
                $p = (float)($v['p'] ?? 0);
                $l = (float)($v['l'] ?? 0);
                $t = (float)($v['t'] ?? 0);
                $tutup = (float)($v['tutup'] ?? 0);
                
                $w = ($l * 2) + ($t * 2) + $tutup;
                $h = ($t * 2) + $p;
                break;
                
            case 'straightTuckEnd':
                $p = (float)($v['p'] ?? 0);
                $l = (float)($v['l'] ?? 0);
                $t = (float)($v['t'] ?? 0);
                $lem = (float)($v['lem'] ?? 1.5);
                
                $w = ($p * 2) + ($l * 2) + $lem;
                $h = $t + $l;
                break;
                
            case 'kebab':
                $p = (float)($v['p'] ?? 0);
                $l = (float)($v['l'] ?? 0);
                $lem = (float)($v['lem'] ?? 1.5);
                
                $w = $p;
                $h = ($l * 2) + $lem;
                break;
                
            case 'kotakMug':
                $p = (float)($v['p'] ?? 0);
                $l = (float)($v['l'] ?? 0);
                $t = (float)($v['t'] ?? 0);
                $lem = (float)($v['lem'] ?? 1.3);
                $kunci_bawah = (float)($v['kunci_bawah'] ?? 8);
                
                $w = (2 * ($p + $l)) + $lem;
                $h = $t + $l + $kunci_bawah;
                break;
                
            case 'burger':
                $p = (float)($v['p'] ?? 0);
                $l = (float)($v['l'] ?? 0);
                $t_bawah = (float)($v['t_bawah'] ?? 4);
                $t_krkn = (float)($v['t_krkn'] ?? 5);
                $t_tutup = (float)($v['t_tutup'] ?? 5);
                
                $w = ($l * 2) + ($t_bawah * 3) + $t_tutup;
                $h = ($t_krkn * 2) + $p;
                break;
                
            case 'customFlat':
                $p = (float)($v['p'] ?? 0);
                $l = (float)($v['l'] ?? 0);
                
                $w = $p;
                $h = $l;
                break;
                
            default:
                $w = 0;
                $h = 0;
        }
        
        return [
            'w' => $w + 2 * $bleed,
            'h' => $h + 2 * $bleed
        ];
    }

    public static function calculateLayoutFit(float $W, float $H, float $w, float $h): int
    {
        if ($w <= 0 || $h <= 0 || $W <= 0 || $H <= 0) return 0;
        
        // 1. Portrait alignment
        $p_cols = (int)floor($W / $w);
        $p_rows = (int)floor($H / $h);
        $p_total = $p_cols * $p_rows;
        
        // 2. Landscape alignment
        $l_cols = (int)floor($W / $h);
        $l_rows = (int)floor($H / $w);
        $l_total = $l_cols * $l_rows;
        
        // 3. Mixed alignment (Portrait first, landscape in remaining)
        $m_p_total = 0;
        if ($p_cols > 0 && $p_rows > 0) {
            $rem_w = $W - ($p_cols * $w);
            $rem_h = $H - ($p_rows * $h);
            
            $add_r = 0;
            if ($rem_w >= $h) {
                $add_r = (int)floor($rem_w / $h) * (int)floor($H / $w);
            }
            $add_b = 0;
            if ($h > 0 && $rem_h >= $w) {
                $add_b = (int)floor(($p_cols * $w) / $h) * (int)floor($rem_h / $w);
            }
            $m_p_total = $p_total + max($add_r, $add_b);
        }
        
        // 4. Mixed alignment (Landscape first, portrait in remaining)
        $m_l_total = 0;
        if ($l_cols > 0 && $l_rows > 0) {
            $rem_w = $W - ($l_cols * $h);
            $rem_h = $H - ($l_rows * $w);
            
            $add_r = 0;
            if ($rem_w >= $w) {
                $add_r = (int)floor($rem_w / $w) * (int)floor($H / $h);
            }
            $add_b = 0;
            if ($w > 0 && $rem_h >= $h) {
                $add_b = (int)floor(($l_cols * $h) / $w) * (int)floor($rem_h / $h);
            }
            $m_l_total = $l_total + max($add_r, $add_b);
        }
        
        return max($p_total, $l_total, $m_p_total, $m_l_total);
    }

    public static function calculatePrice(string $type, array $v, int $qty, string $bahan, string $laminasi, int $warna = 4, bool $isDoorenz = false): array
    {
        $isDigitalBahan = (strpos($bahan, '_dig') !== false || strpos($bahan, 'dtf_') !== false);
        if ($isDigitalBahan && !$isDoorenz) {
            throw new \Exception("Metode cetak digital tidak didukung pada workspace Kakak.");
        }

        $isDigital = $isDoorenz && (strpos($bahan, '_dig') !== false);
        
        // Calculate flat size
        $flatSize = self::calculateFlatSize($type, $v, $bahan, $isDoorenz);
        
        if (isset($flatSize['bawah']) && isset($flatSize['atas'])) {
            // Kotak Tutup Terpisah has separate parts
            $calcB = self::calculateSinglePartPrice($flatSize['bawah']['w'], $flatSize['bawah']['h'], $qty, $bahan, $laminasi, $isDigital, $warna, $isDoorenz);
            $calcA = self::calculateSinglePartPrice($flatSize['atas']['w'], $flatSize['atas']['h'], $qty, $bahan, $laminasi, $isDigital, $warna, $isDoorenz);
            
            return [
                'total_modal' => $calcB['total_modal'] + $calcA['total_modal'],
                'total_jual' => $calcB['total_jual'] + $calcA['total_jual'],
                'harga_satuan' => ($calcB['total_jual'] + $calcA['total_jual']) / $qty,
                'items_per_plano' => min($calcB['items_per_plano'], $calcA['items_per_plano']),
                'total_plano' => $calcB['total_plano'] + $calcA['total_plano'],
                'plano_size' => $calcB['plano_size'],
                'raw_fit' => $isDoorenz ? min($calcB['raw_fit'], $calcA['raw_fit']) : min($calcB['items_per_plano'], $calcA['items_per_plano']),
                'print_division' => $isDoorenz ? $calcB['print_division'] : null,
                'items_per_sheet' => $isDoorenz ? $calcB['items_per_sheet'] : null,
                'is_combined' => $isDoorenz ? ($calcB['is_combined'] || $calcA['is_combined']) : false,
                'is_sablon' => $isDoorenz ? ($calcB['is_sablon'] || $calcA['is_sablon']) : false,
                'print_method' => $isDoorenz ? $calcB['print_method'] : null,
            ];
        }
        
        return self::calculateSinglePartPrice($flatSize['w'], $flatSize['h'], $qty, $bahan, $laminasi, $isDigital, $warna, $isDoorenz);
    }

    private static function calculateSinglePartPrice(float $w, float $h, int $qty, string $bahan, string $laminasi, bool $isDigital, int $warna = 4, bool $isDoorenz = false): array
    {
        // 1. Choose optimal plano size
        $planoSizes = self::getPlanoSizes();
        $bestPlanoKey = '';
        $maxFit = 0;
        
        if ($isDigital) {
            // Digital only uses a3plus (Max printable area 32 x 47 cm for paper, 31 x 46 cm for sticker)
            $bestPlanoKey = 'a3plus';
            $printW = 47.0;
            $printH = 32.0;
            if (strpos(strtolower($bahan), 'stiker') !== false || strpos(strtolower($bahan), 'sticker') !== false) {
                $printW = 46.0;
                $printH = 31.0;
            }
            $maxFit = self::calculateLayoutFit($printW, $printH, $w, $h);
            
            if ($maxFit <= 0) {
                $limitW = $printW;
                $limitH = $printH;
                throw new \Exception("Ukuran bentangan terlalu besar untuk area cetak digital A3+ (maksimal {$limitH} x {$limitW} cm termasuk bleed).");
            }

            // Enforce minimum 5 A3+ sheets for digital printing
            $minSheets = 5;
            $requiredSheets = (int)ceil($qty / $maxFit);
            if ($requiredSheets < $minSheets) {
                $suggestedQty = $minSheets * $maxFit;
                throw new \Exception("Minimal cetak digital adalah {$minSheets} lembar A3+. Untuk ukuran box ini, minimal order Anda adalah {$suggestedQty} pcs.");
            }
        } else {
            // Offset: try plano_79_109, plano_65_100, and plano_90_120
            $offsetPlanos = ['plano_79_109', 'plano_65_100', 'plano_90_120'];
            $bestCostPerPcs = INF;
            
            foreach ($offsetPlanos as $pKey) {
                $fit = self::calculateLayoutFit($planoSizes[$pKey]['w'], $planoSizes[$pKey]['h'], $w, $h);
                if ($fit > 0) {
                    $sheetCost = self::getHargaBahanOffset()[$bahan][$pKey] ?? 0;
                    if ($sheetCost > 0) {
                        $costPerPcs = $sheetCost / $fit;
                        if ($costPerPcs < $bestCostPerPcs) {
                            $bestCostPerPcs = $costPerPcs;
                            $bestPlanoKey = $pKey;
                            $maxFit = $fit;
                        }
                    }
                }
            }
            
            if (empty($bestPlanoKey)) {
                $bestPlanoKey = 'plano_79_109';
                $maxFit = 1;
            }
        }
        
        $rawFit = $maxFit > 0 ? $maxFit : 1;
        if (!$isDigital) {
            $itemsPerPlano = self::getAllowedDivision($rawFit);
        } else {
            $itemsPerPlano = $rawFit;
        }
        
        $totalPlano = (int)ceil($qty / $itemsPerPlano);
        $isSablon = $isDoorenz && ($qty < 1000);
        
        // 2. Base costs
        if ($isSablon) {
            // Screen printing (Sablon Manual 1 Warna)
            if ($isDigital) {
                $bahanCost = self::getHargaBahanDigital()[$bahan] ?? 3000;
                $totalBahan = $totalPlano * $bahanCost;
                $totalFinishing = 50000; // Flat cutting / potong
            } else {
                $sheetCost = self::getHargaBahanOffset()[$bahan][$bestPlanoKey] ?? 4500;
                $insheet = 10; // lower insheet/waste for screen printing
                $totalBahan = ($totalPlano + $insheet) * $sheetCost;
                $totalFinishing = max(15000, $qty * 25); // Potong Jadi
            }
            
            // Screen film/setup cost Rp 150.000 + Rp 500 print fee per piece
            $totalCetak = 150000 + ($qty * 500);
            
            // Sablon is 1 color screen print without laminasi
            $totalModal = $totalBahan + $totalCetak + $totalFinishing;
            $totalJual = $totalModal * 1.35; // 35% markup
        } elseif ($isDigital && $isDoorenz) {
            $bahanCost = self::getHargaBahanDigital()[$bahan] ?? 3000;
            $totalBahan = $totalPlano * $bahanCost;
            $totalCetak = $totalPlano * 4000; // Digital click cost = 4000 per A3+
            
            // Finishing
            $totalFinishing = 50000; // Flat cutting / potong
            if ($laminasi !== 'none') {
                $totalFinishing += $totalPlano * 3000; // 3000 per lembar
            }
            
            $totalModal = $totalBahan + $totalCetak + $totalFinishing;
            $totalJual = $totalModal + 25000; // Rp25.000 setting fee / profit
        } else {
            // Offset
            $sheetCost = self::getHargaBahanOffset()[$bahan][$bestPlanoKey] ?? 4500;
            
            // Insheet = 25 per plano factor
            $insheet = 25;
            $totalBahan = ($totalPlano + $insheet) * $sheetCost;
            
            // Offset printing cost based on jumlah warna (restricted to Doorenz)
            $warnaFactor = 1.0;
            if ($isDoorenz) {
                $warnaFactor = match($warna) {
                    1 => 0.45,
                    2 => 0.60,
                    3 => 0.80,
                    default => 1.00,
                };
            }
            $totalCetak = self::getOffsetPrintingCost($qty) * $warnaFactor;
            
            // Finishing
            $totalFinishing = max(15000, $qty * 25); // Potong Jadi
            if ($laminasi !== 'none') {
                $totalFinishing += $totalPlano * 4000; // Laminasi offset 4000 per plano
            }
            
            $totalModal = $totalBahan + $totalCetak + $totalFinishing;
            
            // For Offset, margin is around 30% or flat markup
            $totalJual = $totalModal * 1.35; // 35% margin for offset
        }
        
        // Round total jual to nearest 500
        $totalJual = ceil($totalJual / 500) * 500;
        $hargaSatuan = $totalJual / $qty;
        
        $printLayout = self::getPrintLayout($itemsPerPlano);
        
        // Label metode cetak
        if ($isSablon) {
            $printMethodLabel = 'Sablon Manual 1 Warna';
        } elseif ($isDigital) {
            $printMethodLabel = 'Digital Print';
        } else {
            $printMethodLabel = match($warna) {
                1 => 'Offset 1 Warna',
                2 => 'Offset 2 Warna',
                3 => 'Offset 3 Warna',
                default => 'Offset Full Color (4 Warna)',
            };
        }
        
        return [
            'total_modal' => $totalModal,
            'total_jual' => $totalJual,
            'harga_satuan' => $hargaSatuan,
            'items_per_plano' => $itemsPerPlano,
            'raw_fit' => $rawFit,
            'print_division' => $printLayout['print_division'],
            'items_per_sheet' => $printLayout['items_per_sheet'],
            'is_combined' => $printLayout['is_combined'],
            'total_plano' => $totalPlano,
            'plano_size' => $planoSizes[$bestPlanoKey]['name'] ?? $bestPlanoKey,
            'is_sablon' => $isSablon,
            'print_method' => $printMethodLabel,
        ];
    }

    private static function getOffsetPrintingCost(int $qty): float
    {
        $tiers = [
            ['min' => 1, 'max' => 1000, 'base' => 850000, 'click' => 850000],
            ['min' => 1001, 'max' => 2000, 'base' => 850000, 'click' => 1300000],
            ['min' => 2001, 'max' => 3000, 'base' => 850000, 'click' => 1500000],
            ['min' => 3001, 'max' => 4000, 'base' => 1100000, 'click' => 1850000],
            ['min' => 4001, 'max' => 5000, 'base' => 1300000, 'click' => 2200000],
            ['min' => 5001, 'max' => 10000, 'base' => 2300000, 'click' => 3400000],
            ['min' => 10001, 'max' => INF, 'base' => 3300000, 'click' => 4400000],
        ];
        
        foreach ($tiers as $t) {
            if ($qty >= $t['min'] && $qty <= $t['max']) {
                return $t['base'] + $t['click'];
            }
        }
        
        return 1700000;
    }

    public static function getAllowedDivision(int $fit): int
    {
        if ($fit < 2) {
            return 1;
        }
        
        if ($fit <= 6) {
            return $fit;
        }
        
        if ($fit % 2 === 0) {
            return $fit;
        }
        
        return $fit - 1;
    }

    public static function getPrintLayout(int $allowedDivision): array
    {
        if ($allowedDivision <= 6) {
            return [
                'print_division' => $allowedDivision,
                'items_per_sheet' => 1,
                'is_combined' => false
            ];
        }
        
        $possiblePrintDivisions = [6, 5, 4, 3, 2];
        foreach ($possiblePrintDivisions as $p) {
            if ($allowedDivision % $p === 0) {
                return [
                    'print_division' => $p,
                    'items_per_sheet' => (int)($allowedDivision / $p),
                    'is_combined' => true
                ];
            }
        }
        
        return [
            'print_division' => 2,
            'items_per_sheet' => (int)ceil($allowedDivision / 2),
            'is_combined' => true
        ];
    }
}
