<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface GatewayContact
{
    public static function make_payment($array);

    public function status(Request $request);
}
