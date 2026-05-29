<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$jobs = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->limit(10)->get();
foreach ($jobs as $job) {
    echo "ID: " . $job->id . " | Failed: " . $job->failed_at . " | Exception: " . substr($job->exception, 0, 150) . "\n";
}
