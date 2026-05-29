<?php
$html = file_get_contents('http://127.0.0.1:8010/user/whatsapp-web/apps');
preg_match('/&quot;version&quot;:&quot;(.*?)&quot;/', $html, $matches);
$version = $matches[1] ?? '';

$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "X-Inertia: true\r\nAccept: application/json\r\nCookie: session=1\r\nX-Inertia-Version: $version\r\n"
    ]
];
$context = stream_context_create($opts);
$result = file_get_contents('http://127.0.0.1:8010/user/whatsapp-web/apps', false, $context);
echo substr($result, 0, 500);
