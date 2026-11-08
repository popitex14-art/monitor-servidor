<?php

namespace App\Console\Commands;

use App\Events\MetricsUpdated;
use App\Services\ServerMetrics;
use Illuminate\Console\Command;
use Throwable;

class BroadcastMetrics extends Command
{
    protected $signature = 'metrics:broadcast';

    protected $description = 'Broadcast the current server metrics';

    public function handle(ServerMetrics $metrics): int
    {
        try {
            MetricsUpdated::dispatch($metrics->snapshot());
            $this->info('Server metrics broadcast successfully.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Metrics broadcast failed: '.$exception->getMessage());

            return Command::FAILURE;
        }
    }
}
