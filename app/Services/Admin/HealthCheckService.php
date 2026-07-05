<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Shared by the Dashboard's SystemHealthWidget (one badge) and the full
 * Health Dashboard page (Phase L) so the underlying checks are written once.
 * Everything here is read-only — no queue worker is running in this
 * environment, so "queue" only ever reports table counts, not live status.
 */
class HealthCheckService
{
    private const APP_VERSION = 'Step 11';

    public function checks(): array
    {
        return [
            'database' => $this->databaseCheck(),
            'cache' => $this->cacheCheck(),
            'storage' => $this->storageCheck(),
            'queue' => $this->queueCheck(),
            'app_version' => self::APP_VERSION,
        ];
    }

    private function databaseCheck(): array
    {
        try {
            DB::select('select 1');

            return ['status' => 'ok'];
        } catch (Throwable) {
            return ['status' => 'error'];
        }
    }

    private function cacheCheck(): array
    {
        try {
            Cache::put('health_check_probe', true, 5);
            $ok = Cache::get('health_check_probe') === true;
            Cache::forget('health_check_probe');

            return ['status' => $ok ? 'ok' : 'error'];
        } catch (Throwable) {
            return ['status' => 'error'];
        }
    }

    private function storageCheck(): array
    {
        try {
            Storage::disk('public')->put('health-check-probe.txt', 'ok');
            $ok = Storage::disk('public')->exists('health-check-probe.txt');
            Storage::disk('public')->delete('health-check-probe.txt');

            return ['status' => $ok ? 'ok' : 'error'];
        } catch (Throwable) {
            return ['status' => 'error'];
        }
    }

    private function queueCheck(): array
    {
        return [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
        ];
    }
}
