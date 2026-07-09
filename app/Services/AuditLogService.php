<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function log(
        string $action,
        ?Model $auditable = null,
        array $newValues = [],
        array $oldValues = [],
        ?int $adminId = null,
        ?string $reason = null,
    ): AuditLog {
        return AuditLog::create([
            'admin_id'        => $adminId,
            'action'          => $action,
            'auditable_type'  => $auditable ? get_class($auditable) : null,
            'auditable_id'    => $auditable?->getKey(),
            'old_values'      => $oldValues ?: null,
            'new_values'      => $newValues ?: null,
            'reason'          => $reason,
        ]);
    }
}
