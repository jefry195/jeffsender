<?php

namespace Modules\WebScraping\App\Jobs;

use App\Models\WebScraping;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScrapeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Waktu maksimum job (15 menit untuk scraping besar)
     */
    public int $timeout = 900;

    /**
     * Tidak perlu retry karena scraping bisa mahal
     */
    public int $tries = 1;

    public function __construct(public WebScraping $record) {}

    public function handle(): void
    {
        try {
            $this->record->update(['status' => 'in_progress']);

            $query = "{$this->record->title} in {$this->record->parameters['state']} {$this->record->parameters['city']} {$this->record->parameters['country']}";

            if ($this->record->type === 'google_maps_no_api') {
                $this->scrapeNoApi($query);
            } else {
                $this->scrapeGooglePlaces($query);
            }
        } catch (\Throwable $e) {
            Log::error('ScrapeJob failed: ' . $e->getMessage(), ['uuid' => $this->record->uuid]);
            $this->record->update(['status' => 'failed']);
        }
    }

    private function scrapeGooglePlaces(string $query): void
    {
        $nextPageToken = null;

        do {
            $params = [
                'query'     => $query,
                'key'       => env('GOOGLE_PLACE_API_KEY'),
            ];
            if ($nextPageToken) {
                $params['pagetoken'] = $nextPageToken;
                // Google requires 2s delay between page token requests
                sleep(2);
            }

            $response = Http::get(
                'https://maps.googleapis.com/maps/api/place/textsearch/json',
                $params
            )->throw()->json();

            foreach ($response['results'] ?? [] as $result) {
                $item = [
                    'name'              => $result['name'],
                    'business_status'   => $result['business_status'] ?? 'OPERATIONAL',
                    'formatted_address' => $result['formatted_address'],
                    'location'          => $result['geometry']['location'] ?? null,
                    'place_id'          => $result['place_id'],
                    'rating'            => $result['rating'] ?? 0,
                    'types'             => $result['types'] ?? [],
                    'website'           => $result['website'] ?? null,
                    'icon'              => [
                        $result['icon'] ?? '',
                        $result['icon_background_color'] ?? '',
                        $result['icon_mask_base_uri'] ?? '',
                    ],
                ];
                $this->record->scraped_data()->updateOrCreate(
                    ['unique_id' => $result['place_id']],
                    ['data' => $item]
                );
            }

            $this->record->increment('query_count');
            $nextPageToken = $response['next_page_token'] ?? null;

        } while ($nextPageToken);

        $this->record->update(['status' => 'completed']);
    }

    private function scrapeNoApi(string $query): void
    {
        $nodePath    = 'C:\\Program Files\\nodejs\\node.exe';
        $scraperPath = base_path('whatsapp-server/scraper.js');
        $command     = "\"$nodePath\" \"$scraperPath\" \"$query\" 2>nul";

        $output = shell_exec($command);

        if (empty(trim($output ?? ''))) {
            throw new \Exception('Tidak ada output dari scraper. Pastikan Node.js & Puppeteer terinstall.');
        }

        preg_match('/\[.*\]/s', $output, $matches);
        if (!isset($matches[0])) {
            throw new \Exception('Output scraper bukan JSON valid: ' . substr($output, 0, 500));
        }

        $scrapedResults = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

        foreach ($scrapedResults as $item) {
            if (empty($item['name'])) continue;

            $uniqueId = md5($item['name'] . ($item['phone'] ?? $item['maps_url'] ?? uniqid()));

            $data = [
                'name'            => $item['name'],
                'business_status' => 'OPERATIONAL',
                'formatted_address' => $item['address'] ?? null,
                'place_id'        => $uniqueId,
                'rating'          => is_numeric($item['rating'] ?? null) ? (float) $item['rating'] : 0,
                'reviews'         => $item['reviews'] ?? null,
                'types'           => [$item['category'] ?? 'business'],
                'phone_number'    => $item['phone'] ?? null,
                'website'         => $item['website'] ?? null,
                'email'           => $item['email'] ?? null,
                'hours'           => $item['hours'] ?? null,
                'maps_url'        => $item['maps_url'] ?? null,
            ];

            $this->record->scraped_data()->updateOrCreate(
                ['unique_id' => $uniqueId],
                ['data' => $data]
            );
        }

        $this->record->update(['status' => 'completed']);
        $this->record->increment('query_count');
    }
}
