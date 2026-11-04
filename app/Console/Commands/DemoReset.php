<?php

namespace App\Console\Commands;

use App\Services\DemoMode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use LogicException;

class DemoReset extends Command
{
    protected $signature = 'orbitcare:demo-reset';
    protected $description = 'Rebuild the explicitly configured local OrbitCare demo database.';

    public function handle(): int
    {
        try {
            DemoMode::assertResetIsSafe();
        } catch (LogicException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->warn('Rebuilding fictional demo records in the configured local demo database.');
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->output->write(Artisan::output());
        $this->info('OrbitCare demo reset complete.');

        return self::SUCCESS;
    }
}
