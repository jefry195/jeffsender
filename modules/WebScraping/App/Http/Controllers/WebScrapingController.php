<?php

namespace Modules\WebScraping\App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Group;
use App\Models\Platform;
use App\Helpers\PageHeader;
use App\Models\WebScraping;
use Illuminate\Http\Request;
use App\Models\WebScrapedData;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Modules\WebScraping\App\Exports\WebScrapedDataExport;
use Modules\WhatsappWeb\App\Services\WhatsAppWebService;

class WebScrapingController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User */
        $user = Auth::user();
        $query = WebScraping::where('user_id', $user->id);
        PageHeader::set(
            title: 'Web Scraping',
            overviews: [
                [
                    'icon' => "bx:list-ul",
                    'title' => 'Total Dataset',
                    'value' => $query->clone()->count(),
                ],
                [
                    'icon' => "bx:checkbox-checked",
                    'title' => 'Completed Jobs',
                    'value' => $query->clone()->where('status', 'completed')->count(),
                ],
                [
                    'icon' => "solar:stopwatch-linear",
                    'title' => 'Pending Jobs',
                    'value' => $query->clone()->where('status', 'pending')->count(),
                ],
                [
                    'icon' => "bx:globe",
                    'title' => 'Total Queries',
                    'value' => $query->clone()->sum('query_count'),
                ],
            ]
        )->addLink(
                'Add New',
                route('user.web-scraping.scrape.create'),
                'bx:plus'
            );

        $scrapingRecords = WebScraping::query()->where('user_id', $user->id)
            ->with('category')
            ->where('module', 'whatsapp-web')
            ->latest()
            ->paginate();


        return Inertia::render('WebScraping/Index', ['scrapingRecords' => $scrapingRecords]);
    }

    public function create()
    {
        validateUserPlan('web_scrape');
        PageHeader::set(
            title: 'Web Scraping',
        );
        $categories = Category::query()->where('type', 'web_scraping')
            ->where('status', 1)->get();
        return Inertia::render('WebScraping/Create', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        validateUserPlan('web_scrape');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:google_places,google_maps_no_api',
            'category_id' => 'required|numeric',
            'parameters' => 'required|array',
            'parameters.city' => 'required|string',
            'parameters.state' => 'required|string',
            'parameters.country' => 'required|string',
        ]);

        $record = WebScraping::create($validated + ['user_id' => Auth::id(), 'module' => 'whatsapp-web']);

        return to_route('user.web-scraping.scrape.show', $record->uuid);
    }

    public function show($uuid)
    {
        PageHeader::set(
            title: 'Web Scraping',
        );

        $user = Auth::user();

        $record = WebScraping::where('uuid', $uuid)
            ->where('module', 'whatsapp-web')
            ->where('user_id', activeWorkspaceOwnerId())
            ->firstOrFail();

        $scraped_data = WebScrapedData::where('web_scraping_id', $record->id)
            ->paginate();

        // Untuk modal import
        $groups = $user->groups()
            ->whatsappWeb()
            ->select('id as value', 'name as label')
            ->latest()
            ->get();

        $platforms = $user->platforms()
            ->whatsappWeb()
            ->select(['id as value', 'name as label', 'uuid'])
            ->get();

        return Inertia::render('WebScraping/Show', [
            'record'       => $record,
            'scraped_data' => $scraped_data,
            'groups'       => $groups,
            'platforms'    => $platforms,
        ]);
    }


    public function store_data($uuid)
    {
       

        $record = WebScraping::where('uuid', $uuid)
            ->where('module', 'whatsapp-web')
            ->where('user_id', activeWorkspaceOwnerId())
            ->with('scraped_data')
            ->firstOrFail();

        $items_to_update = $record->scraped_data->filter(function ($item) {
            return !isset($item->data['phone_number']) || $item->data['phone_number'] === null;
        });

        foreach ($items_to_update as $item) {
            $placeDetails = $this->makeGoogleMapsRequest('details/json', [
                'place_id' => $item->unique_id,
                'fields' => 'name,international_phone_number',
            ]);

            if (isset($placeDetails['result']['international_phone_number'])) {
                $data = $item->data;
                $data['phone_number'] = $placeDetails['result']['international_phone_number'];
                $item->update(['data' => $data]);
            }

            usleep(200000);
        }

        $record->update(['status' => 'completed']);
        return back();
    }

    private function makeGoogleMapsRequest($endpoint, $params)
    {
        $url = config('webscraping.google_maps_place_api_url', 'https://maps.googleapis.com/maps/api/place/') . $endpoint;
        $params['key'] = env('GOOGLE_PLACE_API_KEY');
        return Http::get($url, $params)->throw()->json();
    }
    public function destroy($id)
    {
        $record = WebScraping::where('user_id', activeWorkspaceOwnerId())->findOrFail($id);
        $record->delete();
        return back()->with('danger', 'Deleted Successfully');
    }
    public function destroy_data($id)
    {
        $scraped_data = WebScrapedData::where('id', $id)
            ->whereHas('web_scraping', function ($query) {
                $query->where('user_id', activeWorkspaceOwnerId());
            })
            ->firstOrFail();

        $scraped_data->delete();
        return back()->with('danger', 'Deleted Successfully');
    }

    public function export_data($id)
    {
        $record = WebScraping::where('id', $id)
            ->where('user_id', activeWorkspaceOwnerId())
            ->firstOrFail();

        $fileName = 'scraped_data_' . now() . '.xlsx';

        return Excel::download(
            new WebScrapedDataExport($record->id),
            $fileName,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Import data scraping ke Audience/Customer.
     * - Normalisasi nomor ke format 62 (Indonesia)
     * - Filter nomor rumah (fixed-line) / nomor tidak valid
     * - Opsional: cek via WhatsApp number checker sebelum import
     */
    public function importToAudience(Request $request, WhatsAppWebService $waService)
    {
        $request->validate([
            'scraping_id'   => ['required', 'numeric', 'exists:web_scrapings,id'],
            'group_ids'     => ['required', 'array', 'min:1'],
            'group_ids.*'   => ['numeric', 'exists:groups,id'],
            'check_wa'      => ['boolean'],
            'platform_id'   => ['nullable', 'numeric', 'exists:platforms,id'],
        ]);

        $ownerId    = activeWorkspaceOwnerId();
        $record     = WebScraping::where('id', $request->scraping_id)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $scrapedItems = WebScrapedData::where('web_scraping_id', $record->id)->get();
        $platform     = $request->check_wa && $request->platform_id
            ? Platform::findOrFail($request->platform_id)
            : null;

        $imported  = 0;
        $updated   = 0;
        $skipped   = 0;
        $notOnWa   = 0;

        foreach ($scrapedItems as $item) {
            $contact = $item->data;
            $rawPhone = $contact['phone_number'] ?? null;

            // 1. Lewati jika tidak ada nomor
            if (empty($rawPhone)) {
                $skipped++;
                continue;
            }

            // 2. Normalisasi nomor ke format 62xxxxxxxxxx
            $phone = $this->normalizePhoneNumber($rawPhone);

            // 3. Filter nomor tidak valid (nomor rumah, terlalu pendek, dll)
            if (! $this->isValidMobileNumber($phone)) {
                $skipped++;
                continue;
            }

            // 4. Opsional: cek apakah nomor ada di WhatsApp
            if ($platform) {
                try {
                    $jid = $waService->setJid($phone);
                    $res = $waService->checkNumber($platform->uuid, $jid);

                    if ($res->successful()) {
                        $exists = $res->json('data.0.exists') ?? false;
                        if (!$exists) {
                            $notOnWa++;
                            continue; // Tidak import jika terkonfirmasi bukan WA
                        }
                    }
                } catch (\Exception $e) {
                    // Jika checker error (server down/auth fail), lanjutkan import saja
                    \Log::error('WA Checker Error: ' . $e->getMessage());
                }

                usleep(300000); // Jeda 300ms antar request ke WA server
            }

            // 5. Simpan ke tabel customers
            $customer = Customer::updateOrCreate(
                [
                    'module'   => 'whatsapp-web',
                    'owner_id' => $ownerId,
                    'uuid'     => $phone,
                ],
                [
                    'name'    => $contact['name'] ?? 'Unknown',
                    'picture' => null,
                    'meta'    => [
                        'dial_code'   => 62,
                        'phone'       => ltrim($phone, '62'),
                        'source'      => 'google_maps_scraper',
                        'email'       => $contact['email'] ?? null,
                        'website'     => $contact['website'] ?? null,
                        'is_whatsapp' => $platform ? true : null,
                    ],
                ]
            );

            $customer->groups()->syncWithoutDetaching($request->group_ids);

            if ($customer->wasRecentlyCreated) {
                $imported++;
            } else {
                $updated++;
            }
        }

        $message = "Berhasil: {$imported} baru, {$updated} diperbarui.";
        if ($skipped > 0)   $message .= " {$skipped} non-HP/Kosong dilewati.";
        if ($notOnWa > 0)   $message .= " {$notOnWa} terdeteksi bukan WA.";

        return back()->with('success', $message);
    }

    /**
     * Normalisasi nomor telepon ke format 62xxxxxxxxxx
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Hapus SEMUA karakter non-digit
        $phone = preg_replace('/\D/', '', $phone);

        // Jika diawali 0, ganti dengan 62
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Jika tidak diawali 62 tapi diawali 8 (misal: 812...), tambahkan 62
        if (str_starts_with($phone, '8') && !str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Validasi nomor ponsel Indonesia.
     * Nomor ponsel Indonesia pasti diawali 628... (setelah normalisasi)
     */
    private function isValidMobileNumber(string $phone): bool
    {
        // Minimal 10 digit (628 + 7 digit), Maksimal 15 digit
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return false;
        }

        // Harus diawali 628...
        if (!str_starts_with($phone, '628')) {
            return false;
        }

        return true;
    }
}
