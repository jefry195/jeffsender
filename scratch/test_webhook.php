    <?php

$url = 'http://localhost/api/whatsapp-web/webhook';
$data = [
    'sessionId' => '382dc19f-d06b-4d8b-a132-9f359b31b2ea',
    'event' => 'MESSAGES_UPSERT',
    'data' => [
        'messages' => [
            [
                'key' => [
                    'remoteJid' => '6282171898184@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'TEST_AUTO_REPLY_PHP_'.time(),
                ],
                'message' => [
                    'conversation' => 'halo'
                ]
            ]
        ],
        'type' => 'notify'
    ]
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nHost: wa.doorenzcreative.com\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo $result;
