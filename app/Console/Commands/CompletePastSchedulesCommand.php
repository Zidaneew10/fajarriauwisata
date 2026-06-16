<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Illuminate\Console\Command;

class CompletePastSchedulesCommand extends Command
{
    protected $signature = 'schedules:complete-past';

    protected $description = 'Tandai jadwal yang sudah lewat sebagai selesai (tidak aktif)';

    public function handle(): int
    {
        $count = Schedule::markPastSchedulesAsCompleted();

        $this->info("{$count} jadwal ditandai selesai.");

        return self::SUCCESS;
    }
}
