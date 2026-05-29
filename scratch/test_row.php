<?php
$c = file_get_contents('https://docs.google.com/spreadsheets/d/1EYQlGK3-PKr6GNGcyM1jyEckFka3RMD2ITcHgEpDRrA/export?format=csv&id=1EYQlGK3-PKr6GNGcyM1jyEckFka3RMD2ITcHgEpDRrA&gid=0');
$stream = fopen('php://memory', 'r+');
fwrite($stream, $c);
rewind($stream);
$rows = [];
while (($data = fgetcsv($stream)) !== false) {
    if(isset($data[0]) && strpos($data[0], 'ORD-2026-137') !== false) {
        print_r($data);
    }
}
fclose($stream);
