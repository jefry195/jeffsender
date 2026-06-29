<?php

namespace App\Console\Commands;

use App\Models\Platform;
use App\Services\GoogleSheetsPricelistService;
use Illuminate\Console\Command;

class UpdatePricelistFromGoogleSheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheets:update-pricelist
        {--sheet-url= : Override Google Sheet URL (optional)}
        {--platform=  : Platform ID to use (optional, defaults to first whatsapp-web platform)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download pricelist from Google Sheet and update templates & auto-replies';

    /** Default Google Spreadsheet URL */
    protected string $defaultSheetUrl = 'https://docs.google.com/spreadsheets/d/1iWHN_2zG7MJ6vPD3FPtZXMmqOhozXR1hF9SuOY2EU-w';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Resolve platform
        $platformOpt = $this->option('platform');
        if ($platformOpt) {
            $platform = Platform::find($platformOpt);
        } else {
            $platform = Platform::where('module', 'whatsapp-web')->first();
        }

        if (! $platform) {
            $this->error('No whatsapp-web platform found. Use --platform=ID to specify one.');
            return 1;
        }

        $ownerId    = $platform->owner_id;
        $platformId = $platform->id;

        $this->info("Platform: [{$platform->id}] Owner: " . ($platform->owner->name ?? $ownerId));

        // Resolve sheet URL
        $sheetUrl = $this->option('sheet-url') ?: $this->defaultSheetUrl;
        $this->info("Sheet URL: {$sheetUrl}");

        // Run sync via service
        $service = new GoogleSheetsPricelistService($ownerId, $platformId);

        try {
            $result = $service->sync($sheetUrl);
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }

        $this->info('✅ Pricelist updated successfully!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Templates updated', $result['updated']],
                ['Templates created', $result['created']],
                ['Sheets skipped',    $result['skipped']],
                ['Errors',            count($result['errors'])],
            ]
        );

        if (! empty($result['errors'])) {
            $this->warn('Errors encountered:');
            foreach ($result['errors'] as $err) {
                $this->warn('  - ' . $err);
            }
        }

        return 0;
    }
}
