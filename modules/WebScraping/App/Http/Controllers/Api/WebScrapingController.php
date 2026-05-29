<?php

namespace Modules\WebScraping\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebScraping;
use App\Models\WebScrapedData;
use Illuminate\Support\Facades\Auth;
use Modules\WebScraping\App\Jobs\ScrapeJob;

class WebScrapingController extends Controller
{
    /**
     * Mulai scraping sebagai background job, langsung return tanpa menunggu.
     */
    public function start($uuid)
    {
        $planCheck = validateUserPlan('web_scrape', true);
        if (is_array($planCheck) && $planCheck['status'] === 'error') {
            return response()->json(['error' => $planCheck['message']], 403);
        }

        $record = WebScraping::where('uuid', $uuid)
            ->where('module', 'whatsapp-web')
            ->where('user_id', activeWorkspaceOwnerId())
            ->firstOrFail();

        // Jangan mulai ulang jika sedang berjalan
        if ($record->status === 'in_progress') {
            return response()->json(['status' => 'in_progress', 'message' => 'Scraping sedang berjalan...']);
        }

        $record->update(['status' => 'in_progress']);
        // Hapus data lama jika mau re-scrape
        $record->scraped_data()->delete();

        ScrapeJob::dispatch($record);

        return response()->json([
            'status'  => 'queued',
            'message' => 'Scraping dimulai di latar belakang. Anda bisa membuka halaman lain.',
        ]);
    }

    /**
     * Cek status scraping dan ambil data jika sudah selesai.
     */
    public function status($uuid)
    {
        $record = WebScraping::where('uuid', $uuid)
            ->where('module', 'whatsapp-web')
            ->where('user_id', activeWorkspaceOwnerId())
            ->firstOrFail();

        $response = [
            'status'      => $record->status,
            'query_count' => $record->query_count,
            'total'       => $record->scraped_data()->count(),
        ];

        // Kirim data saat sudah selesai
        if ($record->status === 'completed' || $record->status === 'failed') {
            if ($record->status === 'completed') {
                $response['data'] = WebScrapedData::where('web_scraping_id', $record->id)
                    ->get()
                    ->map(fn($item) => ['id' => $item->id, ...$item->data]);
            }
        }

        return response()->json($response);
    }
}
