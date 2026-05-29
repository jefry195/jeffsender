<?php

use App\Models\Option;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$primary_data = Option::where('key', 'primary_data')->first();
if ($primary_data) {
    $value = $primary_data->value;
    $value['contact_phone'] = '082354506569';
    if (isset($value['socials'])) {
        $value['socials']['whatsapp'] = 'https://wa.me/6282354506569';
    }
    if (isset($value['fancy_banner_six'])) {
        $value['fancy_banner_six']['button_link'] = 'https://wa.me/6282354506569';
    }
    $primary_data->value = $value;
    $primary_data->save();
    echo "Primary data updated.\n";
}

$contact_page = Option::where('key', 'contact_page')->first();
if ($contact_page) {
    $value = $contact_page->value;
    $value['feature_three_description'] = 'https://wa.me/6282354506569';
    $contact_page->value = $value;
    $contact_page->save();
    echo "Contact page updated.\n";
}
