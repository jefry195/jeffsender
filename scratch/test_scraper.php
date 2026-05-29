<?php

require dirname(__DIR__).'/vendor/autoload.php';

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

$client = new Client([
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ]
]);

$query = "restaurants in Jakarta";
$url = "https://www.google.com/search?tbm=lcl&q=" . urlencode($query);

try {
    $response = $client->get($url);
    $html = (string) $response->getBody();
    
    // Pattern for business name in the list
    // Usually inside a div with class OSrXXb or similar
    // Let's look for common patterns in 2024
    
    $results = [];
    
    // Find all business blocks
    preg_match_all('/<div class="OSrXXb"([^>]*)>([^<]*)<\/div>/i', $html, $names);
    
    // Find phone numbers (Pattern: +xx xxxx xxxx or similar)
    preg_match_all('/08[0-9]{2}[- ]?[0-9]{3,4}[- ]?[0-9]{3,4}/', $html, $phones);
    
    foreach ($names[2] as $i => $name) {
        $results[] = [
            'name' => htmlspecialchars_decode($name),
            'phone' => $phones[0][$i] ?? 'N/A',
        ];
    }
    
    if (empty($results)) {
        echo "No results found. HTML length: " . strlen($html) . "\n";
        // Let's dump a bit of HTML to see the structure
        echo substr($html, 0, 1000) . "\n";
    } else {
        print_r($results);
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
