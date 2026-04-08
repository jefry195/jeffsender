<?php

namespace Modules\QAReply\App\Http\Controllers;

use App\Helpers\PageHeader;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\QAReply\App\Http\Controllers\Requests\QaReplyRequest;
use Modules\QAReply\App\Models\QaReply;

class QAReplyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        PageHeader::set('QA Replies')
            ->addModal('Add QA Reply Dataset', 'createModal', 'bx:plus');

        $qaReplies = QaReply::query()
            ->where('owner_id', activeWorkspaceOwnerId())
            ->withCount('items')
            ->paginate();

        $platformModules = getPlatformModules();

        return Inertia::render('QAReplies/Index', [
            'qaReplies' => $qaReplies,
            'modules' => $platformModules,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        PageHeader::set(
            'Add QA Reply Dataset',
            buttons: [
                [
                    'text' => 'Back to List',
                    'url' => '/user/qareply/qareplies',
                    'icon' => 'fe:arrow-left',
                ],
            ]
        );

        return Inertia::render('QAReplies/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QaReplyRequest $request)
    {
        DB::beginTransaction();

        $qaReply = QaReply::create([
            'owner_id' => Auth::id(),
            'module' => $request->input('module'),
            'title' => $request->input('title'),
        ]);

        $items = $request->input('items', []);

        foreach ($items as $item) {
            $item['owner_id'] = Auth::id();
            $qaReply->items()->create($item);
        }

        DB::commit();

        return to_route('user.qareply.qareplies.edit', $qaReply)
            ->with('success', 'QA Reply created successfully');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QaReply $qareply)
    {
        PageHeader::set(
            'Edit Dataset',
            buttons: [
                [
                    'text' => 'Back to List',
                    'url' => '/user/qareply/qareplies',
                    'icon' => 'fe:arrow-left',
                ],
            ]
        );

        $templates = activeWorkspaceOwner()->templates()
            ->where('module', $qareply->module)
            ->get(['name', 'id']);

        return Inertia::render('QAReplies/Create', [
            'qaReply' => $qareply->load('items'),
            'templates' => $templates,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QaReplyRequest $request, QaReply $qareply)
    {

        DB::beginTransaction();

        $qareply->update([
            'title' => $request->input('title'),
        ]);

        $items = $request->collect('items')->map(function ($item) {
            return [
                'id' => $item['id'] ?? null,
                'key' => $item['key'],
                'type' => $item['type'],
                'template_id' => $item['type'] === 'template' ? $item['template_id'] : null,
                'value' => $item['type'] === 'text' ? $item['value'] : null,
            ];
        });

        foreach ($items as $item) {
            $qareply->items()->updateOrCreate(
                [
                    'id' => $item['id'] ?? null,
                ],
                [
                    ...$item,
                    'owner_id' => Auth::id(),
                ]
            );
        }

        DB::commit();

        return to_route('user.qareply.qareplies.index')
            ->with('success', 'Dataset updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QaReply $qareply)
    {
        $qareply->delete();

        return back()->with('success', 'Dataset deleted successfully');
    }
}
