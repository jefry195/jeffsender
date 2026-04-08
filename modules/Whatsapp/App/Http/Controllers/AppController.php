<?php

namespace Modules\Whatsapp\App\Http\Controllers;

use App\Helpers\PageHeader;
use App\Helpers\PlanPerks;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Whatsapp\App\Models\CloudApp;

class AppController extends Controller
{
    public function index()
    {
        /**
         * @var \App\Models\User
         */
        $user = activeWorkspaceOwner();
        $query = CloudApp::where('user_id', $user->id);

        PageHeader::set(
            title: 'My Apps',
        )->addModal('Add New', 'appModal')
            ->overviews([
                [
                    'title' => __('Total Apps'),
                    'icon' => 'bx-grid-alt',
                    'value' => $query->clone()->count(),
                ],
                [
                    'title' => __('Last 7 Days'),
                    'icon' => 'bx:calendar',
                    'value' => $query->clone()->whereBetween('created_at', [now()->subDays(7), now()])->count(),
                ],
                [
                    'title' => __('Last 30 Days'),
                    'icon' => 'bx:calendar',
                    'value' => $query->clone()->whereBetween('created_at', [now()->subDays(30), now()])->count(),
                    'style' => 'bg-purple-600 text-purple-600 bg-opacity-20',
                ],
                [
                    'title' => 'Plan Limit',
                    'value' => PlanPerks::planValue('apps'),
                    'icon' => 'bx:sort-up',
                ],
            ]);
        $apps = $query->filterOn(['name'])->with('platform')->paginate(15);
        $platforms = $user->platforms()->whatsapp()->get();

        return Inertia::render('Apps/Index', [
            'apps' => $apps,
            'platforms' => $platforms,
        ]);
    }

    public function store(Request $request)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        validateWorkspacePlan('apps');

        $request->validate([
            'name' => ['required', 'max:255'],
            'site_link' => ['required', 'string', 'max:255'],
            'platform_id' => ['required', 'exists:platforms,id'],
        ]);

        CloudApp::create([
            'user_id' => activeWorkspaceOwnerId(),
            'name' => $request->name,
            'site_link' => $request->site_link,
            'platform_id' => $request->platform_id,
            'key' => uniqid('key-'),
        ]);

        return back()->with('success', 'Created successfully');
    }

    public function destroy($id)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $app = CloudApp::where('user_id', activeWorkspaceOwnerId())->findOrFail($id);
        $app->delete();

        return back()->with('success', 'Deleted Successfully');
    }

    public function integration($uuid)
    {
        PageHeader::set(title: __('Whatsapp Api Integration'))
            ->buttons([
                [
                    'text' => 'Back',
                    'url' => route('user.whatsapp.apps.index'),
                ],
            ]);

        $app = CloudApp::where('user_id', activeWorkspaceOwnerId())
            ->where('uuid', $uuid)
            ->firstOrFail();

        $authKey = Auth::user()->authKey ?? 'Your Auth Key';

        return Inertia::render('Apps/Integration', [
            'app' => $app,
            'authKey' => $authKey,
        ]);
    }

    public function logs($uuid)
    {
        $app = CloudApp::query()
            ->where('user_id', activeWorkspaceOwnerId())
            ->where('uuid', $uuid)
            ->firstOrFail();

        $query = $app->logs();

        PageHeader::set(title: 'Logs')
            ->overviews([
                [
                    'icon' => 'bx:grid-alt',
                    'value' => $query->clone()->count(),
                    'title' => 'Total',
                ],
                [
                    'icon' => 'bx-check-circle',
                    'value' => $query->clone()->where('status_code', '200')->count(),
                    'title' => 'Success',
                ],
                [
                    'icon' => 'bx:checkbox',
                    'value' => $query->clone()->where('status_code', '!=', '200')->count(),
                    'title' => 'Failed',
                ],
            ]);

        return Inertia::render('Apps/Logs', [
            'app' => $app,
            'logs' => $app->logs()
                ->with('platform')
                ->paginate(),
        ]);
    }
}
