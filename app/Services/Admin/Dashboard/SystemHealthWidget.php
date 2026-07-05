<?php

namespace App\Services\Admin\Dashboard;

use App\Services\Admin\HealthCheckService;

class SystemHealthWidget
{
    public function __construct(private readonly HealthCheckService $health)
    {
    }

    public function data(): array
    {
        $checks = $this->health->checks();

        $statuses = [$checks['database']['status'], $checks['cache']['status'], $checks['storage']['status']];
        $hasFailedJobs = $checks['queue']['failed'] > 0;

        $overall = match (true) {
            in_array('error', $statuses, true) => 'red',
            $hasFailedJobs => 'yellow',
            default => 'green',
        };

        return [
            'overall' => $overall,
            'checks' => $checks,
        ];
    }
}
