<?php

namespace Modules\Whatsapp\App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Group;
use App\Helpers\PlanPerks;
use App\Helpers\PageHeader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = activeWorkspaceOwner()->groups()->filterOn(['name'])->whatsapp();

        $overviews = [
            [
                'icon' => "bx:grid-alt",
                'title' => __('Total Groups'),
                'value' => $query->clone()->count(),
            ],
            [
                'icon' => "bx:calendar",
                'title' => __('Last 7 days Groups'),
                'value' => $query->clone()->whereBetween('created_at', [now()->subDays(7), now()])->count(),
            ],
            [
                'icon' => "bx:calendar",
                'title' => __('Last 30 days Groups'),
                'value' => $query->clone()->whereBetween('created_at', [now()->subDays(30), now()])->count(),
            ]
        ];

        PageHeader::set()->title('Groups')
            ->addModal('Add New', 'groupCreate', 'bx:plus')->overviews($overviews);

        $groups = $query->withCount('customers')->latest()->paginate();

        return Inertia::render('Groups/Index', compact('groups', 'overviews'));
    }

    public function store(Request $request)
    {
          if(env('DEMO_MODE') && auth()->user()->id == 3){
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $validated = $request->validate([
            'name' => 'required|max:200',
        ]);

        /** @var \App\Models\User */
        $user = activeWorkspaceOwner();

        $user->groups()->create([
            'module' => 'whatsapp',
            'name' => $validated['name'],
        ]);

        return back()->with('success', __('Group Created Successfully'));
    }

    public function update(Request $request, $id)
    {
          if(env('DEMO_MODE') && auth()->user()->id == 3){
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $group = Group::find($id);

        $validated = $request->validate([
            'name' => 'required|max:200',
        ]);

        $group->update($validated);

        return back()->with('success', __('Group Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
          if(env('DEMO_MODE') && auth()->user()->id == 3){
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $group = Group::findOrFail($id);

        abort_if($group->owner_id !== activeWorkspaceOwnerId(), 403);

        $group->delete();

        return back()->with('success', __('Group Deleted Successfully'));
    }
}
