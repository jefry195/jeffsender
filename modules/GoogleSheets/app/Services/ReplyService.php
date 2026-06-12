<?php

namespace Modules\GoogleSheets\App\Services;

use App\Abstracts\ReplyServiceAbstract;
use App\Models\Platform;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReplyService extends ReplyServiceAbstract
{
    /**
     * Memproses pesan masuk dan mencarinya di Google Sheets melalui URL Apps Script.
     */
    public function process(): static
    {
        // Cari platform berdasarkan platform_uuid, sessionId atau datasetId
        $platform = Platform::where('uuid', $this->getData('platform_uuid'))
            ->orWhere('uuid', $this->getData('sessionId'))
            ->orWhere('uuid', $this->datasetId)
            ->first();
        
        if (!$platform) {
            Log::error('GoogleSheets: Platform tidak ditemukan untuk sessionId: ' . $this->datasetId);
            return $this;
        }

        // Ambil URL Google Apps Script dari Meta Platform
        $scriptUrl = $platform->getMeta('google_sheets_url');

        if (!$scriptUrl) {
            Log::warning('GoogleSheets: URL Apps Script belum dikonfigurasi di Platform: ' . $platform->name);
            return $this;
        }

        try {
            // Kirim request ke Google Apps Script
            $response = Http::timeout(15)->get($scriptUrl, [
                'q' => $this->messageText, // Kata kunci pencarian (misal: "harga kaos")
                'chat_id' => $this->getData('chat_id'),
                'from_wa' => true
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Jika data ditemukan di Google Sheets
                if (isset($data['found']) && $data['found'] && isset($data['reply'])) {
                    $this->addMessage('text', [
                        'text' => $data['reply']
                    ]);
                }
            } else {
                Log::error('GoogleSheets: Apps Script merespon dengan error: ' . $response->status());
            }
        } catch (\Throwable $th) {
            Log::error('GoogleSheets: Gagal menghubungi Google Apps Script: ' . $th->getMessage());
        }

        return $this;
    }
}
