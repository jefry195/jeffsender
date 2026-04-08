<?php

namespace Modules\Whatsapp\App\Http\Controllers;

use App\Helpers\PageHeader;
use App\Helpers\PlanPerks;
use App\Helpers\Toastr;
use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Whatsapp\App\Requests\TemplateRequest;

class TemplateController extends Controller
{
    public function index()
    {
        if (request('sync_templates') == 1) {
            validateWorkspacePlan('custom_template');
            try {
                $this->syncTemplates();
                Toastr::success(__('Template Synced Successfully'));
            } catch (\Throwable $th) {
                Toastr::danger($th->getMessage());
            }

            return to_route('user.whatsapp.templates.index');
        }

        $query = activeWorkspaceOwner()->templates()->whatsapp();
        PageHeader::set()
            ->title('Templates')
            ->overviews([
                [
                    'icon' => 'bx:grid-alt',
                    'title' => 'Total Templates',
                    'value' => $query->clone()->count(),
                ],
                [
                    'title' => 'Active Templates',
                    'value' => $query->clone()->where('status', 'active')->count(),
                    'icon' => 'bx:check-circle',
                ],
                [
                    'title' => 'Inactive Templates',
                    'value' => $query->clone()->where('status', 'inactive')->count(),
                    'icon' => 'bx-x-circle',
                ],
                [
                    'title' => 'Max Templates Limit',
                    'value' => PlanPerks::planValue('custom_template'),
                    'icon' => 'bx:sort-up',
                ],

            ])
            ->addLink('Sync Template', route('user.whatsapp.templates.index', ['sync_templates' => true]), 'bx:sync')
            ->addLink('Add New', route('user.whatsapp.templates.create'), 'bx:plus');

        $templates = Template::with('platform:id,name')
            ->where('owner_id', activeWorkspaceOwnerId())
            ->whatsapp()
            ->filterOn(['name', 'status'])
            ->latest()
            ->paginate();

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create()
    {

        PageHeader::set()
            ->title('Create Template')
            ->buttons([
                [
                    'text' => 'Back',
                    'url' => route('user.whatsapp.templates.index'),
                ],
            ]);

        return Inertia::render('Templates/Create');
    }

    public function store(TemplateRequest $request)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        Template::create([
            'module' => 'whatsapp',
            'owner_id' => activeWorkspaceOwnerId(),
            'uuid' => uniqid(),
            'name' => $request->input('name'),
            'type' => $request->input('message_type'),
            'meta' => $request->input('meta'),
            'status' => $request->input('status'),
        ]);

        return to_route('user.whatsapp.templates.index')->with('success', 'Template Saved Successfully');
    }

    public function show(Template $template)
    {
        PageHeader::set()->title('Device Template Show')->buttons([
            [
                'text' => 'Back to List',
                'url' => route('user.whatsapp.templates.index'),
            ],
        ]);

        return Inertia::render('Templates/Show', [
            'template' => $template,
        ]);
    }

    public function edit(Template $template)
    {

        PageHeader::set()->title('Edit Template')
            ->buttons([
                [
                    'text' => 'Back to List',
                    'url' => route('user.whatsapp.templates.index'),
                ],
            ]);

        return Inertia::render('Templates/Create', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, Template $template)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $template->update([
            'name' => $request->input('name'),
            'meta' => $request->input('meta'),
            'status' => $request->input('status'),
        ]);

        return to_route('user.whatsapp.templates.index')
            ->with('success', 'Template Updated Successfully');
    }

    public function destroy(Template $template)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $template->validateOwnership();
        $template->delete();

        return back()->with('success', 'Template Deleted Successfully');
    }

    /**
     * Sync all whatsapp platforms templates with the current user's templates
     *
     * @return void
     */
    protected function syncTemplates()
    {
        $platforms = activeWorkspaceOwner()->platforms()->whatsapp()->get();
        foreach ($platforms as $device) {
            $device->syncTemplates();
        }
    }

    public function getDeviceTemplateList(Request $request)
    {
        $user = activeWorkspaceOwner();
        $templates = $user
            ->templates()
            ->where('module', 'whatsapp')
            ->when($request->filled('type'), function ($q) use ($request) {
                $q->where('type', $request->input('type'));
                if (request('type') == 'template') {
                    $q->orWhereNull('type');
                }

                return $q;
            })
            ->when(
                $request->filled('platform_id') && $request->get('type'),
                function ($q) use ($request) {
                    if (request('type') == 'interactive') {
                        return $q;
                    }

                    $q->where('platform_id', $request->input('platform_id'))->orWhereNull('platform_id');
                }
            )

            ->latest()
            ->get();

        return $templates;
    }

    public function copy(Template $template)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $template->validateOwnership();
        $copyTemplate = $template->replicate();
        $copyTemplate->name .= ' - Copy';
        $copyTemplate->status = 'draft';
        $copyTemplate->save();

        return back()->with('success', 'Template Copied Successfully');
    }
}
