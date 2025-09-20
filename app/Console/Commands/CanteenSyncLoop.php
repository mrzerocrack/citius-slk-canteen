<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

#[AsCommand(name: 'canteen:sync-loop', description: 'Loop every 10 minutes: POST to sync endpoints with key_code')]
class CanteenSyncLoop extends Command
{
    /**
     * Explicit signature to avoid empty-name issues if attribute discovery fails.
     */
    protected $signature = 'canteen:sync-loop';

    protected $description = 'Loop every 10 minutes: POST to sync endpoints with key_code';

    public function handle(): int
    {
        $host = rtrim(env('SYNC_HOST_URL', 'http://sks.canteen'), '/');
        $prefix = trim(env('SYNC_PREFIX', 'api'), '/');
        $interval = (int) env('SYNC_INTERVAL', 600); // seconds
        $verifySsl = filter_var(env('SYNC_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN);

        $endpoints = [
            'sync_canteen',
            'sync_slp',
            'sync_employee',
            'sync_employee_cc',
            'sync_log',
        ];

        $this->info("Starting canteen:sync-loop. Interval: {$interval}s");

        while (true) {
            foreach ($endpoints as $ep) {
                $url = $host.'/'.($prefix !== '' ? $prefix.'/' : '').$ep;
                try {
                    $http = Http::timeout(120)->asForm();
                    if (! $verifySsl) {
                        $http = $http->withoutVerifying();
                    }

                    $response = $http->post($url, [
                            'key_code' => 'T()tt3nh@m',
                        ]);

                    if ($response->successful()) {
                        $this->info("{$ep} DONE");
                    } else {
                        $this->error("{$ep} FAILED [status={$response->status()}]");
                        Log::error('Sync endpoint failed', [
                            'endpoint' => $ep,
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    $this->error("{$ep} FAILED [exception]: ".$e->getMessage());
                    Log::error('Sync endpoint exception', [
                        'endpoint' => $ep,
                        'exception' => $e,
                    ]);
                }
            }

            $this->info('TASK DONE. Sleeping...');
            sleep($interval);
        }

        return self::SUCCESS;
    }
}
