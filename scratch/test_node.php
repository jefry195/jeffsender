<?php
echo "Node version: " . shell_exec('node -v 2>&1') . "<br>";
echo "Current directory: " . getcwd() . "<br>";
$scraper = "C:\\xampp\\htdocs\\jeffsender\\whatsapp-server\\scraper.js";
echo "Scraper exists: " . (file_exists($scraper) ? 'Yes' : 'No') . "<br>";
$command = "node \"$scraper\" \"test query\" 2>&1";
echo "Command: $command <br>";
echo "Output: " . shell_exec($command);
