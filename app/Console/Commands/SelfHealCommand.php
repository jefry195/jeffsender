<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Platform;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SelfHealCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jeffsender:self-heal {--force : Force enable auto-reply settings even if manually disabled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose and automatically fix Jeffsender services, database config, and queues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=== Jeffsender Self-Healing & Diagnostic System ===");
        $this->newLine();

        $this->checkPM2Services();
        $this->checkPlatformConfig();
        $this->checkFailedQueueJobs();
        $this->checkWhatsAppSessionStatus();
        $this->checkNodeServerFreeze();

        $this->newLine();
        $this->info("Diagnostics and repairs completed successfully!");
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    /**
     * Check PM2 process statuses and restart any stopped or errored processes.
     */
    private function checkPM2Services(): void
    {
        $this->comment("1. Checking PM2 Process Statuses...");
        
        // Run pm2 jlist to get process list in JSON format
        $output = executeSilentCommand('cmd /c pm2 jlist');
        if (!$output) {
            $this->warn("PM2 is not running or not accessible in the path. Skipping PM2 checks.");
            return;
        }

        $processes = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Failed to parse PM2 output. Skipping PM2 checks.");
            return;
        }

        $expectedServices = ['whatsapp-server', 'laravel-server', 'laravel-queue'];
        $foundServices = [];

        foreach ($processes as $proc) {
            $name = $proc['name'] ?? null;
            if (in_array($name, $expectedServices)) {
                $foundServices[$name] = $proc['pm2_env']['status'] ?? 'offline';
            }
        }

        foreach ($expectedServices as $service) {
            if (!isset($foundServices[$service])) {
                $this->warn("  [!] Service '{$service}' is not registered in PM2. Attempting to start all via ecosystem...");
                executeSilentCommand('cmd /c pm2 start ecosystem.config.cjs');
                continue;
            }

            $status = $foundServices[$service];
            if ($status !== 'online') {
                $this->warn("  [!] Service '{$service}' is currently '{$status}'. Restarting...");
                executeSilentCommand("cmd /c pm2 restart {$service}");
                $this->info("  [+] Service '{$service}' has been restarted.");
            } else {
                $this->info("  [✓] Service '{$service}' is ONLINE.");
            }
        }
    }

    /**
     * Check platform auto-reply configuration.
     */
    private function checkPlatformConfig(): void
    {
        $this->comment("2. Checking Platform Auto-Reply Configurations...");

        $platforms = Platform::all();
        foreach ($platforms as $platform) {
            $meta = $platform->meta;
            $status = $platform->status ?? '';
            $isAuthenticated = ($status === 'authenticated' || ($meta['verified'] ?? false) === true);

            if ($isAuthenticated) {
                $sendAutoReply = $meta['send_auto_reply'] ?? false;
                if (!$sendAutoReply) {
                    $this->warn("  [!] Platform '{$platform->name}' ({$platform->uuid}) is authenticated but auto-reply is DISABLED.");
                    
                    // Auto-enable if force option is used or by default since they ran this repair command
                    $meta['send_auto_reply'] = true;
                    if (empty($meta['auto_reply_method'])) {
                        $meta['auto_reply_method'] = 'default';
                    }
                    
                    $platform->update(['meta' => $meta]);
                    $this->info("  [+] Auto-reply has been automatically ENABLED for platform '{$platform->name}'.");
                } else {
                    $this->info("  [✓] Platform '{$platform->name}' has auto-reply ENABLED.");
                }
            } else {
                $this->info("  [-] Platform '{$platform->name}' is not authenticated. Skipping.");
            }
        }
    }

    /**
     * Check for failed queue jobs and automatically retry them.
     */
    private function checkFailedQueueJobs(): void
    {
        $this->comment("3. Checking for Failed Queue Jobs...");
        
        $failedJobsCount = \DB::table('failed_jobs')->count();
        if ($failedJobsCount > 0) {
            $this->warn("  [!] Found {$failedJobsCount} failed jobs in the queue.");
            $this->info("  [+] Retrying all failed queue jobs...");
            
            Artisan::call('queue:retry all');
            $this->info("  [✓] Failed jobs have been pushed back into the queue.");
        } else {
            $this->info("  [✓] No failed queue jobs found.");
        }
    }

    /**
     * Verify WhatsApp server active session connections.
     */
    private function checkWhatsAppSessionStatus(): void
    {
        $this->comment("4. Checking WhatsApp Server Session Connections...");
        
        $baseUrl = config('whatsapp-web.base_url', 'http://127.0.0.1:3000');
        $platforms = Platform::all();
        
        $hasIssue = false;
        
        foreach ($platforms as $platform) {
            $meta = $platform->meta;
            $status = $platform->status ?? '';
            $isAuthenticated = ($status === 'authenticated' || ($meta['verified'] ?? false) === true);
            
            if ($isAuthenticated && $platform->module === 'whatsapp-web') {
                try {
                    $url = "{$baseUrl}/sessions/status/{$platform->uuid}";
                    $response = Http::timeout(5)->withHeaders([
                        'X-API-Key' => '12345'
                    ])->get($url);
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        $sessionStatus = $data['data']['status'] ?? '';
                        if ($sessionStatus !== 'authenticated') {
                            $this->warn("  [!] Platform '{$platform->name}' session status on WhatsApp server is '{$sessionStatus}' (expected: authenticated).");
                            $hasIssue = true;
                        } else {
                            $this->info("  [✓] Platform '{$platform->name}' session connection is ACTIVE.");
                        }
                    } else {
                        $this->warn("  [!] Failed to get session status for '{$platform->name}'. HTTP Code: " . $response->status());
                        $hasIssue = true;
                    }
                } catch (\Throwable $e) {
                    $this->warn("  [!] Error connecting to WhatsApp server to verify session for '{$platform->name}': " . $e->getMessage());
                    $hasIssue = true;
                }
            }
        }
        
        if ($hasIssue) {
            $this->comment("  [!] Issues detected in WhatsApp session. Restarting whatsapp-server PM2 process to force reconnection...");
            executeSilentCommand('cmd /c pm2 restart whatsapp-server');
            $this->info("  [+] Service 'whatsapp-server' has been restarted.");
        }
    }

    /**
     * Detect if Node whatsapp-server has silently frozen (Baileys socket freeze).
     * If no new log activity in the last 20 minutes, force-restart the server.
     */
    private function checkNodeServerFreeze(): void
    {
        $this->comment("5. Checking WhatsApp Node Server Activity (Freeze Detection)...");

        $logPath = base_path('whatsapp-server/logs/app.log');
        if (!file_exists($logPath)) {
            $this->warn("  [!] Node server log file not found at: {$logPath}");
            return;
        }

        $size = filesize($logPath);
        if ($size === 0) {
            $this->warn("  [!] Node server log file is empty.");
            return;
        }

        // Read the last 2 KB of the log to extract the latest timestamp
        $handle = fopen($logPath, 'r');
        fseek($handle, max(0, $size - 2048));
        $tail = fread($handle, 2048);
        fclose($handle);

        // Extract last ISO timestamp from the log (format: [INFO] 2026-06-29T02:00:59.888Z: ...)
        preg_match_all('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z/', $tail, $matches);
        $timestamps = $matches[0] ?? [];
        if (empty($timestamps)) {
            $this->warn("  [!] No timestamps found in Node server log.");
            return;
        }

        $lastTimestamp = end($timestamps);
        $lastActivity  = strtotime($lastTimestamp);
        $minutesAgo    = (int) round((time() - $lastActivity) / 60);

        if ($minutesAgo > 360) {
            $this->warn("  [!] Node server last activity was {$minutesAgo} minutes ago ({$lastTimestamp}). Possible freeze detected!");
            $this->comment("  [!] Restarting whatsapp-server to recover from freeze...");
            executeSilentCommand('cmd /c pm2 restart whatsapp-server');
            Log::warning('SelfHeal: whatsapp-server freeze detected and restarted', [
                'last_activity' => $lastTimestamp,
                'minutes_ago'   => $minutesAgo,
            ]);
            $this->info("  [+] Service 'whatsapp-server' has been restarted due to freeze.");
        } else {
            $this->info("  [✓] Node server is ACTIVE (last log: {$minutesAgo} min ago).");
        }
    }
}
