<?php

namespace Modules\Whatsapp\App\Http\Controllers;

use App\Helpers\PageHeader;
use App\Helpers\PlanPerks;
use App\Helpers\Toastr;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Rules\Phone;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Whatsapp\App\Imports\CustomerListImport;

class CustomerController extends Controller
{
    use Uploader;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request('export')) {
            return $this->exportCustomerList();
        }
        /** @var \App\Models\User */
        $user = activeWorkspaceOwner();
        $query = $user->customers()->filterOn(['name', 'uuid'])->whatsapp();
        
        $rows = request('rows', 25);
        if ($rows == 'all') {
            $rows = $query->count() ?: 25;
        }

        $customers = $query->latest()
            ->with(['groups:id,name', 'platform:id,name'])
            ->paginate($rows)
            ->withQueryString();

        $groups = $user->groups()
            ->whatsapp()
            ->latest()
            ->get(['id', 'name']);

        $platforms = $user->platforms()->whatsapp()->get(['id', 'name']);

        $dialCodes = json_decode(file_get_contents(database_path('json/country_codes.json')), true);
        $overviews = [
            [
                'icon' => 'bx:grid-alt',
                'title' => __('Total Audiences'),
                'value' => $query->clone()->count(),
            ],
            [
                'icon' => 'bx:calendar',
                'title' => __('Last 7 Days Audiences'),
                'value' => $query->clone()->whereBetween('created_at', [now()->subDays(7), now()])->count(),
            ],
            [
                'title' => __('Last 30 Days Audiences'),
                'value' => $query->clone()->whereBetween('created_at', [now()->subDays(30), now()])->count(),
                'icon' => 'bx:calendar',
                'style' => 'bg-purple-600 text-purple-600 bg-opacity-20',
            ],
            [
                'title' => __('Max Audiences Limit'),
                'icon' => 'hugeicons:limitation',
                'value' => PlanPerks::planValue('contacts'),
            ],
        ];
        PageHeader::set()
            ->title('Audiences')
            ->overviews($overviews)
            ->addModal('Import', 'importModal', 'bx:import')
            ->addLink(__('Export'), route('user.whatsapp.customers.index', ['export' => true]), 'bx:export', type: 'a', target: '_blank')
            ->addLink(__('Add New'), route('user.whatsapp.customers.create'), 'bx:plus');

        return Inertia::render('Customers/Index', compact(
            'customers',
            'groups',
            'dialCodes',
            'platforms'
        ));
    }

    public function create()
    {

        validateWorkspacePlan('contacts');

        PageHeader::set()->title(__('Add New Customer'))->buttons([
            [
                'text' => __('Back'),
                'url' => route('user.whatsapp.customers.index'),
            ],
        ]);

        $dialCodes = file_get_contents(base_path('database/json/country_codes.json'));
        $dialCodes = json_decode($dialCodes, true);
        $dialCodes = array_map(function ($item) {
            return [
                'name' => $item['code'].' ('.$item['dial_code'].')',
                'id' => $item['dial_code'],
            ];
        }, $dialCodes);

        $groups = activeWorkspaceOwner()->groups()->whatsapp()->latest()->get(['id', 'name']);

        return Inertia::render('Customers/Create', compact('groups', 'dialCodes'));
    }

    public function store(Request $request)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        validateWorkspacePlan('contacts');

        $validated = $request->validate(
            [
                'name' => 'required|max:200',
                'phone' => ['required', new Phone],
                'dial_code' => ['required'],
                'picture' => 'nullable',
                'group_ids' => 'required|array',
                'groups_ids.*' => 'numeric|exists:groups,id',
            ],
            [],
            [
                'group_ids' => 'groups',
            ]
        );

        $user = activeWorkspaceOwner();

        $modifiedDialCode = str($validated['dial_code'])->remove('+');
        $customer = $user->customers()->create([
            'module' => 'whatsapp',
            'name' => $validated['name'],
            'picture' => $validated['picture'] ?? null,
            'uuid' => "{$modifiedDialCode}{$request->phone}",
            'meta' => [
                'dial_code' => $modifiedDialCode,
                'phone' => $request->phone,
            ],
        ]);

        if (isset($validated['group_ids']) && count($validated['group_ids'])) {
            $customer->groups()->sync($validated['group_ids']);
        }

        Toastr::success('Created Successfully');

        return to_route('user.whatsapp.customers.index');
    }

    public function edit(Customer $customer)
    {
        PageHeader::set(
            title: __('Edit Audience'),
            buttons: [
                [
                    'text' => 'Back',
                    'url' => route('user.whatsapp.customers.index'),

                ],
            ]
        );
        $groupIds = $customer->groups()->pluck('id');

        $groups = activeWorkspaceOwner()->groups()->whatsapp()->latest()->get(['id', 'name']);

        $dialCodes = file_get_contents(base_path('database/json/country_codes.json'));
        $dialCodes = json_decode($dialCodes, true);
        $dialCodes = array_map(function ($item) {
            return [
                'name' => $item['code'].' ('.$item['dial_code'].')',
                'id' => $item['dial_code'],
            ];
        }, $dialCodes);

        $customer->dial_code = str($customer->meta['dial_code'] ?? '')->prepend('+')->toString();
        $customer->phone = $customer->meta['phone'] ?? '';

        return Inertia::render('Customers/Edit', compact('groups', 'dialCodes', 'groupIds', 'customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $validated = $request->validate([
            'name' => 'required|max:200',
            'phone' => ['required', new Phone],
            'dial_code' => ['required'],
            'picture' => 'nullable',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'numeric|exists:groups,id',
        ]);

        $modifiedDialCode = str($validated['dial_code'])->remove('+');
        $customer->update([
            'name' => $validated['name'],
            'picture' => $validated['picture'] ?? null,
            'uuid' => "{$modifiedDialCode}{$request->phone}",
            'meta' => [
                'dial_code' => $modifiedDialCode,
                'phone' => $request->phone,
            ],
        ]);

        $customer->groups()->sync($validated['group_ids'] ?? []);

        return to_route('user.whatsapp.customers.index')->with('success', 'Updated Successfully ');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $customer->delete();

        return back()->with('success', __('Customer Deleted Successfully'));
    }

    public function bulkDestroy(Request $request)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }

        $validated = $request->validate([
            'customer_ids.*' => 'numeric|exists:customers,id',
        ]);

        activeWorkspaceOwner()->customers()->whereIn('id', $validated['customer_ids'])->delete();

        return back()->with('success', __('Customers Deleted Successfully'));
    }

    public function bulkImport(Request $request)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $validated = $request->validate([
            'group_ids' => 'required|array',
            'group_ids.*' => 'numeric|exists:groups,id',
            'csv_file' => ['required', 'file'],
        ]);

        Excel::import(new CustomerListImport('whatsapp', $validated['group_ids']), $request->file('csv_file'));

        return back()->with('success', __('Bulk Import Successfully'));
    }

    private function exportCustomerList()
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }

        return Excel::download(
            new \Modules\Whatsapp\App\Exports\CustomerListExport('whatsapp'),
            'whatsapp-customers-list_'.now().'.csv',
            \Maatwebsite\Excel\Excel::CSV,
            [
                'Content-Type' => 'text/csv',
            ]
        );
    }

    public function bulkAssignGroup(Request $request)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }

        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'numeric|exists:customers,id',
            'group_ids' => 'required|array',
            'group_ids.*' => 'numeric|exists:groups,id',
        ]);

        $customers = activeWorkspaceOwner()->customers()->whereIn('id', $validated['customer_ids'])->get();

        foreach ($customers as $customer) {
            $customer->groups()->sync($validated['group_ids']);
        }

        return back()->with('success', __('Groups Assigned Successfully'));
    }
}
