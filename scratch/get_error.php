<?php
$lines = file('storage/logs/laravel.log');
$lines = array_slice($lines, -5000);
$found = false;
foreach($lines as $i => $line) {
    if (strpos($line, 'HandleIncomingMessageJob') !== false) {
        $found = true;
        // Search backwards for the "[2026-" that starts this block
        for ($j = $i; $j >= 0; $j--) {
            if (strpos($lines[$j], '[2026-') === 0) {
                // Print only the error type and message up to 300 chars
                echo substr($lines[$j], 0, 500) . "\n";
                exit;
            }
        }
    }
}
