<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Yantrana\Components\Scheduling\SchedulingEngine;

class ProcessScheduledMessages extends Command
{
    protected $signature = 'scheduling:process {--limit=50 : Number of messages to process per run}';

    protected $description = 'Process scheduled messages that are ready to be sent';

    public function handle(SchedulingEngine $schedulingEngine)
    {
        $limit = (int) $this->option('limit');

        $this->info('Processing scheduled messages...');

        $result = $schedulingEngine->processScheduledMessages($limit);

        $this->info("Processed: {$result['processed']}, Failed: {$result['failed']}, Total: {$result['total']}");

        return Command::SUCCESS;
    }
}
