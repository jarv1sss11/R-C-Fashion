<?php

namespace App\Services\Admin\Dashboard\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Shared last-6-months bucketing used by every widget that feeds a chart
 * (new users, vendor registrations, orders, revenue, recommendation clicks) —
 * written once so each widget isn't reinventing the same GROUP BY month query.
 */
trait BuildsMonthlySeries
{
    private function monthlySeries(string $table, string $dateColumn, ?string $sumColumn = null, array $wheres = []): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

        $query = DB::table($table)
            ->selectRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') as month, ".
                ($sumColumn ? "SUM({$sumColumn}) as value" : 'COUNT(*) as value'))
            ->where($dateColumn, '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month');

        foreach ($wheres as $column => $value) {
            $query->where($column, $value);
        }

        $rows = $query->pluck('value', 'month');

        return $months->mapWithKeys(fn ($month) => [
            Carbon::createFromFormat('Y-m', $month)->format('M') => (float) ($rows[$month] ?? 0),
        ])->all();
    }
}
