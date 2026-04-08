<?php

namespace App\Http\Controllers\Admin\Logs;

use App\Helpers\PageHeader;
use App\Http\Controllers\Controller;
use App\Models\Platform;
use Inertia\Inertia;

class PlatformController extends Controller
{
    public function index()
    {
        $query = Platform::query()->with(['owner:id,name']);

        PageHeader::set(
            title: 'Platforms',
            overviews: [
                [
                    'icon' => 'bx:list-ul',
                    'title' => 'Total Platforms',
                    'value' => $query->clone()->count(),
                ],
                [
                    'icon' => 'bx:checkbox-checked',
                    'title' => 'Active Platforms',
                    'value' => $query->clone()->whereIn('status', ['authenticated', 'connected'])->count(),
                ],
                [
                    'icon' => 'bx:x-circle',
                    'title' => 'Inactive Platforms',
                    'value' => $query->clone()->whereNotIn('status', ['authenticated', 'connected'])->count(),
                ],
            ]
        );

        $platforms = $query->clone()->paginate();

        return Inertia::render('Admin/Logs/Platforms/Index', [
            'platforms' => $platforms,
        ]);
    }

    public function destroy(Platform $platform)
    {
        $platform->delete();

        return back()->with('success', 'Platform removed successfully');
    }
}
