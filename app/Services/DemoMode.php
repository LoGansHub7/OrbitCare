<?php

namespace App\Services;

use LogicException;

class DemoMode
{
    public static function enabled(): bool
    {
        return ! app()->environment('production') && (bool) config('orbitcare.demo_mode');
    }

    public static function assertResetIsSafe(): void
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');

        if (! self::enabled() || $database !== (string) config('orbitcare.demo_database')) {
            throw new LogicException('Demo reset is disabled. Enable ORBITCARE_DEMO_MODE and use the configured local demo database.');
        }
    }
}
