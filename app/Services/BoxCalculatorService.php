<?php

namespace App\Services;

class BoxCalculatorService
{
    public static function getProductTypes(): array
    {
        return [
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
    }

    public static function getBahanOptions(): array
    {
        return [
            'kraft290_off' => 'Kraft 290gr',
            'ivory250_off' => 'Ivory 250gr',
            'duplex250_off' => 'Duplex 250gr',
            'ap310_dig' => 'Art Paper 310 gr (Digital)',
            'ap260_dig' => 'Art Paper 260 gr (Digital)',
        ];
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
            // Ivory 250gr hanya tersedia dalam plano 79x109 di Dooren'z
            'ivory250_off' => [
                'plano_79_109' => 4500,
            ],
            // Duplex 250gr hanya tersedia dalam plano 79x109 di Dooren'z
            'duplex250_off' => [
                'plano_79_109' => 4500,
            ],
        ];
    }

    public static function getHargaBahanDigital(): array
    {
        return [
            'ap310_dig' => 4000,
            'ap260_dig' => 3300,
        ];
    }

    public static function calculateFlatSize(string $type, array $v): array
    {
        // Default bleed = 0.75 cm
        $bleed = 0.75;
        
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
            if ($rem_h >= $w) {
                $add_b = (int)floor(($p_cols * $w) / h) * (int)floor($rem_h / $w); // Wait, this division should be by $h
            }
            // Fix division
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

    public static function calculatePrice(string $type, array $v, int $qty, string $bahan, string $laminasi): array
    {
        $isDigital = strpos($bahan, '_dig') !== false;
        
        // Calculate flat size
        $flatSize = self::calculateFlatSize($type, $v);
        
        if (isset($flatSize['bawah']) && isset($flatSize['atas'])) {
            // Kotak Tutup Terpisah has separate parts
            $calcB = self::calculateSinglePartPrice($flatSize['bawah']['w'], $flatSize['bawah']['h'], $qty, $bahan, $laminasi, $isDigital);
            $calcA = self::calculateSinglePartPrice($flatSize['atas']['w'], $flatSize['atas']['h'], $qty, $bahan, $laminasi, $isDigital);
            
            return [
                'total_modal' => $calcB['total_modal'] + $calcA['total_modal'],
                'total_jual' => $calcB['total_jual'] + $calcA['total_jual'],
                'harga_satuan' => ($calcB['total_jual'] + $calcA['total_jual']) / $qty,
                'items_per_plano' => min($calcB['items_per_plano'], $calcA['items_per_plano']),
                'total_plano' => $calcB['total_plano'] + $calcA['total_plano'],
                'plano_size' => $calcB['plano_size']
            ];
        }
        
        return self::calculateSinglePartPrice($flatSize['w'], $flatSize['h'], $qty, $bahan, $laminasi, $isDigital);
    }

    private static function calculateSinglePartPrice(float $w, float $h, int $qty, string $bahan, string $laminasi, bool $isDigital): array
    {
        // 1. Choose optimal plano size
        $planoSizes = self::getPlanoSizes();
        $bestPlanoKey = '';
        $maxFit = 0;
        
        if ($isDigital) {
            // Digital only uses a3plus
            $bestPlanoKey = 'a3plus';
            $maxFit = self::calculateLayoutFit($planoSizes['a3plus']['w'], $planoSizes['a3plus']['h'], $w, $h);
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
        
        $itemsPerPlano = $maxFit > 0 ? $maxFit : 1;
        $totalPlano = (int)ceil($qty / $itemsPerPlano);
        
        // 2. Base costs
        if ($isDigital) {
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
            
            // Offset Click Cost based on Qty (Assume 4 warna full color by default)
            $totalCetak = self::getOffsetPrintingCost($qty);
            
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
        
        return [
            'total_modal' => $totalModal,
            'total_jual' => $totalJual,
            'harga_satuan' => $hargaSatuan,
            'items_per_plano' => $itemsPerPlano,
            'total_plano' => $totalPlano,
            'plano_size' => $planoSizes[$bestPlanoKey]['name'] ?? $bestPlanoKey
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
}
