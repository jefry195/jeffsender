<?php
$content = file_get_contents('.env');
$lines = explode("\n", $content);
$filtered = [];
foreach ($lines as $line) {
    if (strpos($line, "\0") === false && trim($line) !== '') {
        $filtered[] = trim($line);
    }
}
// Specifically filtered out anything that looks like the corrupted strings
$final = [];
foreach($filtered as $line) {
    if (preg_match('/^[A-Z_]+=/', $line) || $line === '') {
        $final[] = $line;
    }
}

file_put_contents('.env', implode("\n", $final));
echo "Fixed .env\n";
