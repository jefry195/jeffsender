<?php

use App\Models\AutoReply;
use Illuminate\Support\Facades\DB;

// User: jefry.m95@gmail.com (ID: 2)
// Platform: 6282261567685 (ID: 1)
$userId = 2;
$platformId = 1;
$module = 'whatsapp-web';

$qaItems = DB::table('qa_reply_items')->where('owner_id', $userId)->get();

$count = 0;
foreach ($qaItems as $item) {
    // Check if keyword already exists for this platform
    $keywordsStr = $item->key;
    $keywordsArr = array_map('trim', explode(',', $keywordsStr));
    
    // Create AutoReply
    AutoReply::create([
        'module' => $module,
        'owner_id' => $userId,
        'platform_id' => $platformId,
        'template_id' => ($item->type === 'template') ? $item->template_id : null,
        'keywords' => $keywordsArr,
        'message_type' => ($item->type === 'template') ? 'template' : 'text',
        'message_template' => ($item->type === 'text') ? ($item->value ?? $item->key) : null,
        'status' => 'active',
    ]);
    $count++;
}

echo "Migrated $count QA Reply items to AutoReply module for Whatsapp-Web." . PHP_EOL;
