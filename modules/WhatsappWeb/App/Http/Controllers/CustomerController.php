<?php

namespace Modules\WhatsappWeb\App\Http\Controllers;

use App\Exports\CustomerListExport;
use App\Helpers\PageHeader;
use App\Helpers\PlanPerks;
use App\Helpers\Toastr;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Platform;
use App\Models\WebScrapedData;
use App\Models\WebScraping;
use App\Rules\Phone;
use App\Services\AssetService;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use libphonenumber\PhoneNumberUtil;
use Maatwebsite\Excel\Facades\Excel;
use Modules\WhatsappWeb\App\Imports\CustomerListImport;
use Modules\WhatsappWeb\App\Services\WhatsAppWebService;

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
        $queryBuilder = $user->customers()->whatsappWeb();
        
        // Filter berdasarkan group jika ada di request
        if (request()->filled('group_id')) {
            $queryBuilder->whereHas('groups', function($q) {
                $q->where('groups.id', request('group_id'));
            });
        }

        $query = $queryBuilder->clone()->filterOn(['name', 'uuid']);

        $rows = request('rows', 25);
        if ($rows == 'all') {
            $totalCount = $query->clone()->count();
            // Show all on one page — max 10000 as a memory safety cap
            $rows = max(1, min($totalCount, 10000));

            // When showing all rows, page > 1 makes no sense — redirect to page 1
            if (request('page', 1) > 1) {
                return redirect()->to(
                    route('user.whatsapp-web.customers.index', array_merge(
                        request()->except('page'),
                        ['page' => 1]
                    ))
                );
            }
        }


        // Snapshot overviews BEFORE paginate() so LIMIT/OFFSET don't corrupt the count
        $overviews = [
            [
                'icon'  => 'bx:list-ul',
                'title' => 'Total Contact',
                'value' => $query->clone()->count(),
            ],
            [
                'icon'  => 'bx:checkbox-checked',
                'title' => 'Last 7 Days',
                'value' => $query->clone()->whereBetween('customers.created_at', [now()->subDays(7), now()])->count(),
            ],
            [
                'icon'  => 'hugeicons:limitation',
                'title' => 'Max Contact',
                'value' => PlanPerks::planValue('contacts'),
            ],
        ];

        $customers = $query->latest()
            ->with('groups')
            ->paginate($rows)
            ->withQueryString();

        $header = PageHeader::set()
            ->title('Customers')
            ->overviews($overviews);

        if (PlanPerks::checkPlan('group_extractor', true)['status'] === 'success') {
            $header->addModal('Elite Group Harvester', 'groupHarvesterModal', 'bx:collection');
        }

        $header->addModal('Import From Device', 'importFromDeviceModal', 'bx:mobile')
            ->addModal('Import CSV', 'importModal', 'bx:file')
            ->addModal('Import ScrapeData', 'importFromScrapeDataModal', 'bx:globe')
            ->addLink(
                __('Export CSV'),
                route('user.whatsapp-web.customers.index', ['export' => true]),
                'bx-download',
                '_blank'
            )
            ->addLink(__('Add New'), route('user.whatsapp-web.customers.create'), 'bx-plus');

        $groups = $user->groups()
            ->whatsappWeb()
            ->select('id as value', 'name as label')
            ->latest()
            ->get();

        $platforms = $user->platforms()->whatsappWeb()->select(['id as value', 'name as label', 'id', 'name', 'meta'])->get();
        $scraped_record = WebScraping::where('user_id', $user->id)
            ->select(['id as value', 'title as label'])
            ->where('module', 'whatsapp-web')
            ->get();


        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'groups' => $groups,
            'platforms' => $platforms,
            'scraped_record' => $scraped_record,
        ]);
    }

    public function create()
    {

        PageHeader::set()->title('Create Contact Number')
            ->addBackLink(route('user.whatsapp-web.customers.index'));

        $groups = activeWorkspaceOwner()
            ->groups()
            ->whatsappWeb()
            ->select('id as value', 'name as label')
            ->latest()
            ->get();

        return Inertia::render('Customers/Create', compact('groups'));
    }

    public function store(Request $request, AssetService $assetService)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $validated = $request->validate(
            [
                'name' => 'required|max:200',
                'phone' => ['required', new Phone],
                'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'group_ids' => 'required|array',
                'groups_ids.*' => 'numeric|exists:groups,id',
            ],
            [],
            [
                'group_ids' => 'groups',
            ]
        );

        /**
         * @var \App\Models\User
         */
        $user = activeWorkspaceOwner();
        if ($request->hasFile('picture')) {
            $picture = $assetService->upload('image');
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $formattedNumber = $phoneUtil->parse($request->phone, 'ID');

        $customer = $user->customers()->create([
            'module' => 'whatsapp-web',
            'name' => $validated['name'],
            'picture' => $picture?->path ?? null,
            'uuid' => str($request->phone)->remove('+'),
            'meta' => [
                'dial_code' => $formattedNumber->getCountryCode(),
                'phone' => $formattedNumber->getNationalNumber(),
            ],

        ]);

        if (isset($validated['group_ids']) && count($validated['group_ids'])) {
            $customer->groups()->sync($validated['group_ids']);
        }

        Toastr::success('Created Successfully');

        return to_route('user.whatsapp-web.customers.index');
    }

    public function edit(Customer $customer)
    {
        PageHeader::set(
            title: 'Edit customer information',
            buttons: [
                [
                    'text' => 'Back',
                    'url' => route('user.whatsapp-web.customers.index'),

                ],
            ]
        );
        $groupIds = $customer->groups()->pluck('id');
        $groups = activeWorkspaceOwner()->groups()->whatsappWeb()->select('id as value', 'name as label')->latest()->get();

        return Inertia::render(
            'Customers/Edit',
            compact('groups', 'groupIds', 'customer')
        );
    }

    public function update(Request $request, Customer $customer)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $validated = $request->validate([
            'name' => 'required|max:200',
            'phone' => ['required', new Phone],
            'group_ids' => 'nullable|array',
            'groups_ids.*' => 'numeric|exists:groups,id',
        ]);

        if ($request->hasFile('picture')) {

            $request->validate([
                'picture' => 'image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $validated['picture'] = $this->saveFile($request, 'picture');

            if ($customer->picture) {
                $this->removeFile($customer->picture);
            }
        }

        $customer->update([
            'name' => $validated['name'],
            'picture' => $this->uploadFile('picture', $customer->picture),
            'uuid' => str($request->phone)->remove('+'),
        ]);

        if (isset($validated['group_ids']) && count($validated['group_ids'])) {
            $customer->groups()->sync($validated['group_ids']);
        }

        return to_route('user.whatsapp-web.customers.index')->with('success', 'Updated Successfully ');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return back()->with('danger', __('Customer Deleted Successfully'));
    }

    public function bulkImport(Request $request)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $request->validate([
            'group_ids' => ['required', 'array'],
            'group_ids.*' => ['numeric', 'exists:groups,id'],
            'csv_file' => ['required', 'file'],
        ]);

        Excel::import(
            new CustomerListImport('whatsapp-web', $request->group_ids), // Pass group_ids to constructor
            $request->file('csv_file')
        );

        return back()->with('success', __('Bulk Import Successfully'));
    }

    public function bulkDelete(Request $request)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }

        $validated = $request->validate([
            'customer_ids.*' => 'numeric|exists:customers,id',
        ]);

        activeWorkspaceOwner()->customers()->whereIn('id', $validated['customer_ids'])->delete();

        return back()->with('success', __('Customers Deleted Successfully'));
    }

    public function importFromDevice(Request $request)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $request->validate([
            'platform_ids' => ['required', 'array'],
            'platform_ids.*' => ['required', 'numeric', 'exists:platforms,id', 'distinct'],
            'group_ids' => ['required', 'array'],
            'group_ids.*' => ['required', 'numeric', 'exists:groups,id', 'distinct'],
        ]);

        $total = 0;

        foreach ($request->platform_ids as $platformId) {
            $platform = Platform::findOrFail($platformId);
            $platformContacts = DB::table('Contact')
                ->where('sessionId', $platform->uuid)
                ->get();

            foreach ($platformContacts as $contact) {
                $name = $contact->notify ?? $contact->verifiedName ?? $contact->name ?? 'Unknown';

                if ($name === 'Unknown') {
                    $chat = DB::table('Chat')
                        ->where('sessionId', $platform->uuid)
                        ->where('id', $contact->id)
                        ->whereNotNull('name')
                        ->first();
                    $name = $chat->name ?? 'Unknown';
                }

                if ($name === 'Unknown') {
                    $message = DB::table('Message')
                        ->where('sessionId', $platform->uuid)
                        ->where('remoteJid', $contact->id)
                        ->whereNotNull('pushName')
                        ->first();
                    $name = $message->pushName ?? 'Unknown';
                }

                $picture = $contact->imgUrl;
                $phone = str($contact->id)->before('@')->toString();

                $isPhone = str($contact->id)->contains('@s.whatsapp.net');
                $isValidPhone = $isPhone && str($phone)->length() >= 8;

                if (! $isValidPhone) {
                    continue;
                }

                $phoneUtil = PhoneNumberUtil::getInstance();
                $formattedNumber = $phoneUtil->parse($phone, 'ID'); // Changed from 'BD' to 'ID' for Indonesia

                $updateData = [
                    'picture' => $picture,
                    'meta' => [
                        'dial_code' => $formattedNumber->getCountryCode(),
                        'phone' => $formattedNumber->getNationalNumber(),
                    ],
                ];

                if ($name !== 'Unknown') {
                    $updateData['name'] = $name;
                }

                $customer = $platform->customers()->where('uuid', $phone)->first();

                if ($customer) {
                    $customer->update($updateData);
                } else {
                    $updateData['module'] = 'whatsapp-web';
                    $updateData['owner_id'] = $platform->owner_id;
                    $updateData['uuid'] = $phone;
                    if (!isset($updateData['name'])) {
                        $updateData['name'] = 'Unknown';
                    }
                    $customer = $platform->customers()->create($updateData);
                    $total++;
                }

                $customer->groups()->syncWithoutDetaching($request->group_ids);
            }
        }

        return back()->with('success', $total.__(' Customers Imported Successfully'));
    }

    public function getGroupsByPlatform(Request $request, WhatsAppWebService $service)
    {
        $platform = Platform::where('id', $request->platform_id)->firstOrFail();
        $response = $service->getGroups($platform->uuid);
        
        if (isset($response['data']['chats'])) {
            return response()->json($response['data']['chats']);
        }

        return response()->json([]);
    }

    public function importFromGroup(Request $request, WhatsAppWebService $service)
    {
        PlanPerks::checkPlan('group_extractor');

        $request->validate([
            'platform_id' => 'required|exists:platforms,id',
            'wa_group_id' => 'required|string',
            'group_ids' => 'required|array',
            'group_ids.*' => 'exists:groups,id',
        ]);

        $platform = Platform::findOrFail($request->platform_id);
        $groupMeta = $service->getGroupMetaData($platform->uuid, $request->wa_group_id);

        if (!$groupMeta['success']) {
            return back()->with('danger', __('Failed to fetch group members.'));
        }

        $participants = $groupMeta['data']['participants'] ?? [];
        $total = 0;

        // Prevent timeout for large groups
        set_time_limit(0);
        
        // IMPORTANT: Release session lock so user can still browse other pages 
        // while this long process runs. This prevents "419 Page Expired" or "Logout" feel.
        session_write_close();

        $phoneUtil = PhoneNumberUtil::getInstance();
        $ownerId = $platform->owner_id;
        $targetGroupIds = $request->group_ids;

        foreach ($participants as $participant) {
            $jid = $participant['phoneNumber'] ?? $participant['id'];
            if (!$jid) continue;

            $phone = str($jid)->before('@')->toString();
            
            // Filter only valid individual numbers
            if (str($jid)->contains('@g.us')) continue;
            if (!str($jid)->contains('@s.whatsapp.net')) continue;

            try {
                $formattedNumber = $phoneUtil->parse($phone, 'ID');
                
                $customer = Customer::updateOrCreate(
                    [
                        'module' => 'whatsapp-web',
                        'owner_id' => $ownerId,
                        'uuid' => $phone,
                    ],
                    [
                        'name' => $participant['name'] ?? 'Unknown',
                        'meta' => [
                            'dial_code' => $formattedNumber->getCountryCode(),
                            'phone' => $formattedNumber->getNationalNumber(),
                        ],
                    ]
                );

                $customer->groups()->syncWithoutDetaching($targetGroupIds);
                if ($customer->wasRecentlyCreated) {
                    $total++;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return back()->with('success', $total . __(' Group Members Imported Successfully'));
    }

    public function importFromScrapeData(Request $request)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $request->validate([
            'scraped_record_ids' => ['required', 'array'],
            'scraped_record_ids.*' => ['numeric', 'exists:web_scrapings,id', 'distinct'],
            'group_ids' => ['required', 'array'],
            'group_ids.*' => ['required', 'numeric', 'exists:groups,id', 'distinct', 'min:1'],
        ]);

        $total = 0;

        $records = WebScraping::whereIn('id', $request->scraped_record_ids)->get();
        $scraped_data = WebScrapedData::whereIn('web_scraping_id', $records->pluck('id'))->get();
        foreach ($scraped_data as $data) {
            $contact = $data->data;

            if (isset($contact['phone_number'])) {

                $phoneUtil = PhoneNumberUtil::getInstance();
                $formattedNumber = $phoneUtil->parse($contact['phone_number'], 'ID');

                $customer = Customer::updateOrCreate(
                    [
                        'module' => 'whatsapp-web',
                        'owner_id' => $data->web_scraping->user_id,
                        'uuid' => str_replace([' ', '-', '+'], '', $contact['phone_number']),
                    ],
                    [
                        'name' => $contact['name'],
                        'picture' => $contact['icon'][0] ?? null,
                        'module' => 'whatsapp-web',
                        'meta' => [
                            'dial_code' => $formattedNumber->getCountryCode(),
                            'phone' => $formattedNumber->getNationalNumber(),
                        ],
                    ]
                );
                $customer->groups()->sync($request->group_ids);
                if ($customer->wasRecentlyCreated) {
                    $total++;
                }
            }
        }

        return back()->with('success', $total.__(' Contacts Imported Successfully'));
    }

    private function exportCustomerList()
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }

        return Excel::download(
            new CustomerListExport('whatsapp-web'),
            'whatsapp-web-customers-list_'.now().'.csv',
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

    public function bulkVerify(Request $request, WhatsAppWebService $whatsAppWebService)
    {
        set_time_limit(0); 

        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'numeric|exists:customers,id',
            'platform_id' => 'required|numeric|exists:platforms,id',
        ]);

        $platform = Platform::findOrFail($validated['platform_id']);
        $customers = activeWorkspaceOwner()->customers()->whereIn('id', $validated['customer_ids'])->get();

        $success = 0;
        $failed = 0;

        foreach ($customers as $customer) {
            try {
                $jid = $whatsAppWebService->setJid($customer->uuid);
                $res = $whatsAppWebService->checkNumber($platform->uuid, $jid);

                if ($res->successful()) {
                    $exists = $res->json('data.0.exists') ?? false;
                    
                    if ($exists) {
                        $meta = $customer->meta ?? [];
                        $meta['is_whatsapp'] = true;
                        $customer->update(['meta' => $meta]);
                        $success++;
                    } else {
                        // Delete if not on WhatsApp
                        $customer->delete();
                        $failed++;
                    }
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }

        $message = "$success ". __('Numbers Verified Successfully');
        if ($failed > 0) {
            $message .= " & " . "$failed " . __('Inactive Numbers Deleted');
        }

        return back()->with('success', $message);
    }
}