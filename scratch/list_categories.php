<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach(App\Models\Category::all() as $c) {
    echo $c->id . ': ' . $c->title . PHP_EOL;
}
