<?php

namespace App\Repositories;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Sole data-access point for `audit_logs`. AuditLogService owns the
 * write-time shaping (enum → string, model → morph columns); this class
 * only reads/writes the table itself.
 */
class AuditLogRepository
{
    public function create(array $attributes): AuditLog
    {
        return AuditLog::query()->create($attributes);
    }

    /**
     * @param  array{admin_id?: int, action?: string, auditable_type?: string, date_from?: string, date_to?: string}  $filters
     */
    public function filtered(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('admin', 'auditable')
            ->when($filters['admin_id'] ?? null, fn ($q, $value) => $q->where('admin_id', $value))
            ->when($filters['action'] ?? null, fn ($q, $value) => $q->where('action', $value))
            ->when($filters['auditable_type'] ?? null, fn ($q, $value) => $q->where('auditable_type', $value))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
