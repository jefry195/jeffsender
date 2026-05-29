<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\WhatsappWeb\App\Services\WhatsAppWebService;
use App\Models\Platform;
use Illuminate\Support\Facades\Log;

class GoogleSheetTracker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheets:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Google Sheets for completed tasks and notify customers via WhatsApp.';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppWebService $waService)
    {
        $this->info("Memulai daemon Google Sheet Tracker...");
        
        $runLoop = true;
        // Loop abadi untuk terus berjalan di latar belakang
        while ($runLoop) {
            $this->processSheets($waService);
            
            // Tunggu 60 detik sebelum mengecek lagi
            sleep(60);
        }
        
        return 0;
    }

    private function processSheets(WhatsAppWebService $waService)
    {
        // Cari semua App di "My Apps" yang link-nya mengarah ke Google Sheets
        $apps = \Modules\WhatsappWeb\App\Models\WhatsappWebApp::where('site_link', 'like', '%docs.google.com/spreadsheets%')->get();

        if ($apps->isEmpty()) {
            return;
        }

        foreach ($apps as $app) {
            $platform = $app->platform;
            if (!$platform) continue;
            
            $platformUuid = $platform->uuid;
            $rawUrl = trim($app->site_link);
            
            // Ekstrak ID spreadsheet dan GID
            preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $rawUrl, $idMatches);
            preg_match('/gid=([0-9]+)/', $rawUrl, $gidMatches);
            
            if (empty($idMatches[1])) continue;
            
            $sheetId = $idMatches[1];
            $gid = isset($gidMatches[1]) ? $gidMatches[1] : '0';
            
            $csvUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&id={$sheetId}&gid={$gid}";

            $response = Http::timeout(15)->get($csvUrl);

            if (!$response->successful()) continue;

            $csvData = $response->body();
            
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $csvData);
            rewind($stream);
            
            $rows = [];
            while (($data = fgetcsv($stream)) !== false) {
                $rows[] = $data;
            }
            fclose($stream);
            
            if (count($rows) > 0) array_shift($rows); // Hapus header

            $notifiedFile = 'notified_invoices_' . $sheetId . '_' . $gid . '.json';
            $notifiedInvoices = Storage::exists($notifiedFile) ? json_decode(Storage::get($notifiedFile), true) : [];
            if (!is_array($notifiedInvoices)) $notifiedInvoices = [];

            $isFirstRun = empty($notifiedInvoices);
            $newNotifications = 0;

            foreach ($rows as $row) {
                if (count($row) < 12) continue;

                $orderNo = trim($row[0]);
                $customerName = trim($row[3]);
                $waNumber = trim($row[4]);
                
                $statusFinishing = isset($row[11]) ? trim($row[11]) : '';

                // Lewati jika tidak ada nomor order
                if (empty($orderNo) || $orderNo === '-') continue;
                
                $isFinished = false;
                if (trim(strtolower($statusFinishing)) === 'finishing selesai (siap kirim)') {
                    $isFinished = true;
                }

                if ($isFinished && !in_array($orderNo, $notifiedInvoices)) {
                    $notifiedInvoices[] = $orderNo;

                    if ($isFirstRun) {
                        $newNotifications++;
                        continue;
                    }

                    $cleanNumber = preg_replace('/[^0-9]/', '', $waNumber);
                    if (str_starts_with($cleanNumber, '0')) $cleanNumber = '62' . substr($cleanNumber, 1);
                    else if (str_starts_with($cleanNumber, '8')) $cleanNumber = '62' . $cleanNumber;

                    if (empty($cleanNumber) || strlen($cleanNumber) < 10) continue;

                    $jid = $cleanNumber . "@s.whatsapp.net";
                    $messageText = "Halo *$customerName*,\n\nPesanan Anda dengan No. Order *$orderNo* saat ini telah *SELESAI* dikerjakan dan siap untuk diambil/dikirim.\n\nTerima kasih telah mempercayakan pesanan Anda kepada kami! 🙏";

                    try {
                        $waService->sendMessage($platformUuid, $jid, ['text' => $messageText], 'text');
                        $newNotifications++;
                        sleep(2);
                    } catch (\Exception $e) {
                        Log::error("Gagal mengirim WA: " . $e->getMessage());
                    }
                }
            }

            if ($newNotifications > 0) {
                Storage::put($notifiedFile, json_encode($notifiedInvoices));
            }
        }
    }
}
