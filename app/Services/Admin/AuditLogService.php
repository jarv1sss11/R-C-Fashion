<?php

namespace App\Services\Admin;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\AuditLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * The only place an administrator action gets recorded. Called explicitly
 * from each Admin\*Service method — deliberately not a model Observer, since
 * an observer on User/Product would also capture buyer/vendor self-service
 * edits, which must never appear in the administrator audit trail.
 */
class AuditLogService
{
    public function __construct(private readonly AuditLogRepository $repository)
    {
    }

    public function record(
        User $admin,
        AuditAction $action,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $reason = null,
    ): AuditLog {
        return $this->repository->create([
            'admin_id' => $admin->id,
            'action' => $action->value,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'reason' => $reason,
        ]);
    }

    /**
     * @param  array{admin_id?: int, action?: string, auditable_type?: string, date_from?: string, date_to?: string}  $filters
     */
    public function filtered(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->filtered($filters, $perPage);
    }
}
