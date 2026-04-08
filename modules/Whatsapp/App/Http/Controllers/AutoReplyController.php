<?php

namespace Modules\Whatsapp\App\Http\Controllers;

use App\Helpers\PageHeader;
use App\Http\Controllers\Controller;
use App\Models\AutoReply;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Whatsapp\App\Services\TemplateValidation;

class AutoReplyController extends Controller
{
    public function index(Request $request)
    {

        $query = activeWorkspaceOwner()->autoReplies()->whatsapp();

        PageHeader::set()->title('Auto Replies')->buttons([
            [
                'url' => route('user.whatsapp.auto-replies.create'),
                'text' => 'Add New',
            ],
        ])->overviews([
            [
                'icon' => 'bx:grid-alt',
                'value' => $query->clone()->count(),
                'title' => 'Total Auto Replies',
            ],
            [
                'icon' => 'bx:check-circle',
                'value' => $query->clone()->active()->count(),
                'title' => 'Active Auto Replies',
            ],
            [
                'icon' => 'bx:x-circle',
                'value' => $query->clone()->active()->count(),
                'title' => 'Inactive Auto Replies',
            ],
        ]);

        $autoReplies = $query->clone()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('keyword', 'like', "%{$request->search}%")
                    ->orWhereRelation('template', 'name', 'like', '%'.$request->search.'%');
            })
            ->with('platform:id,name', 'template:id,name')
            ->latest()
            ->paginate();

        return Inertia::render(
            'AutoReplies/Index',
            compact(
                'autoReplies',
            )
        );
    }

    public function create()
    {
        validateWorkspacePlan('auto_reply');

        PageHeader::set()->title('Add Auto Reply')->addBackLink(route('user.whatsapp.auto-replies.index'));

        $platforms = activeWorkspaceOwner()
            ->platforms()
            ->where('module', 'whatsapp')
            ->get(['id', 'name']);

        $sort_codes = ['{name}'];

        $templates = activeWorkspaceOwner()->templates()
            ->where('module', 'whatsapp')
            ->get();

        return Inertia::render('AutoReplies/Create', compact('platforms', 'sort_codes', 'templates'));
    }

    public function store(Request $request)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        validateWorkspacePlan('auto_reply');

        $validation_params = [
            'platform_id' => 'required|numeric|exists:platforms,id',
            'keywords' => 'required|array',
            'message_type' => 'required|in:text,template,interactive',
            'message_template' => 'required_if:message_type,text|string',
            'template_id' => 'required_if:message_type,template|nullable|numeric|exists:templates,id',
            'meta' => 'nullable',
            'status' => 'required|in:active,inactive',
        ];

        $validation_message = [
            'keywords.required' => 'The keywords field is required',
            'platform_id.required' => 'The device field is required',
            'message.required' => 'The message field is required',
            'template_id.required' => 'The Template is required',
        ];

        $validated = TemplateValidation::validate(
            $request,
            $validation_params,
            $validation_message
        );
        $validated['module'] = 'whatsapp';

        activeWorkspaceOwner()->autoReplies()->create($validated);

        return to_route('user.whatsapp.auto-replies.index')->with('success', __('Created Successfully'));
    }

    public function edit(Request $request, AutoReply $autoReply)
    {
        PageHeader::set()->title('Edit Auto Reply')->addBackLink(route('user.whatsapp.auto-replies.index'));

        $templates = activeWorkspaceOwner()->templates()->whatsapp()->get(['name', 'id', 'meta']);
        $platforms = activeWorkspaceOwner()->platforms()->whatsapp()->get(['name', 'id']);
        $templates = activeWorkspaceOwner()->templates()
            ->where('module', 'whatsapp')
            ->get();

        return Inertia::render('AutoReplies/Create', compact('platforms', 'autoReply', 'templates'));
    }

    public function update(Request $request, AutoReply $autoReply)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $validation_params = [
            'platform_id' => 'required|numeric|exists:platforms,id',
            'keywords' => 'required|array',
            'message_type' => 'required|in:text,template,interactive',
            'message_template' => 'required_if:message_type,text|nullable|string',
            'template_id' => 'required_if:message_type,template|nullable|numeric|exists:templates,id',
            'meta' => 'nullable',
            'status' => 'required|in:active,inactive',
        ];

        $validation_message = [
            'keywords.required' => 'The keywords field is required',
            'platform_id.required' => 'The device field is required',
            'message.required' => 'The message field is required',
            'template_id.required' => 'The Template is required',
        ];

        $validated = TemplateValidation::validate($request, $validation_params, $validation_message);

        $autoReply->update($validated);

        return to_route('user.whatsapp.auto-replies.index')
            ->with('success', 'Updated successfully');
    }

    public function destroy(AutoReply $autoReply)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $autoReply->delete();

        return back()->with('success', 'Deleted successfully');
    }
}
