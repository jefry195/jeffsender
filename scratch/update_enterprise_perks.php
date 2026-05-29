<?php

use App\Models\Plan;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$enterprisePlans = Plan::where('title', 'Enterprise')->get();

foreach ($enterprisePlans as $plan) {
    /** @var Plan $plan */
    $data = $plan->data;
    $data['group_extractor'] = [
        'value' => true,
        'overview' => 'Elite Group Harvester'
    ];
    $plan->update(['data' => $data]);
    echo "Updated plan ID: {$plan->id}\n";
}

echo "Done!\n";
