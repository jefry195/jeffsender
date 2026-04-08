<?php

namespace Modules\Whatsapp\App\Http\Controllers;

use Inertia\Inertia;
use App\Helpers\PageHeader;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        PageHeader::set('Whatsapp overviews');
        return Inertia::render('Dashboard');
    }
}
