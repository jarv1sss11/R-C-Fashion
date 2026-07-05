<?php

namespace App\Services\Admin;

use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function paginated(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%")))
            ->when($filters['role'] ?? null, fn ($q, $value) => $q->where('role', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function suspend(User $admin, User $target, ?string $reason = null): void
    {
        if ($target->id === $admin->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot suspend your own account.',
            ]);
        }

        $oldStatus = $target->status;

        $target->update(['status' => 'suspended']);

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::UserSuspended,
            subject: $target,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'suspended'],
            reason: $reason,
        );
    }

    public function activate(User $admin, User $target): void
    {
        $oldStatus = $target->status;

        $target->update(['status' => 'active']);

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::UserActivated,
            subject: $target,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'active'],
        );
    }

    public function assignAdmin(User $admin, User $target): void
    {
        if ($target->role === 'admin') {
            throw ValidationException::withMessages([
                'user' => 'This user is already an administrator.',
            ]);
        }

        $oldRole = $target->role;

        $target->update(['role' => 'admin']);

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::UserRoleChanged,
            subject: $target,
            oldValues: ['role' => $oldRole],
            newValues: ['role' => 'admin'],
        );
    }
}
