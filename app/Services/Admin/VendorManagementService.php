<?php

namespace App\Services\Admin;

use App\Enums\AuditAction;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VendorManagementService
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function paginated(array $filters): LengthAwarePaginator
    {
        return VendorProfile::query()
            ->with('user')
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q
                ->where('store_name', 'like', "%{$value}%")
                ->orWhereHas('user', fn ($q) => $q
                    ->where('name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%"))))
            ->when($filters['approval_status'] ?? null, fn ($q, $value) => $q->where('approval_status', $value))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function approve(User $admin, VendorProfile $vendor, string $reason): void
    {
        $old = $vendor->approval_status;

        $vendor->update(['approval_status' => 'approved']);

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::VendorApproved,
            subject: $vendor,
            oldValues: ['approval_status' => $old],
            newValues: ['approval_status' => 'approved'],
            reason: $reason,
        );
    }

    public function reject(User $admin, VendorProfile $vendor, string $reason): void
    {
        $old = $vendor->approval_status;

        $vendor->update(['approval_status' => 'rejected']);

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::VendorRejected,
            subject: $vendor,
            oldValues: ['approval_status' => $old],
            newValues: ['approval_status' => 'rejected'],
            reason: $reason,
        );
    }

    public function suspend(User $admin, VendorProfile $vendor, string $reason): void
    {
        $old = $vendor->user->status;

        $vendor->user->update(['status' => 'suspended']);

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::VendorSuspended,
            subject: $vendor,
            oldValues: ['status' => $old],
            newValues: ['status' => 'suspended'],
            reason: $reason,
        );
    }

    public function restore(User $admin, VendorProfile $vendor, string $reason): void
    {
        $old = $vendor->user->status;

        $vendor->user->update(['status' => 'active']);

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::VendorRestored,
            subject: $vendor,
            oldValues: ['status' => $old],
            newValues: ['status' => 'active'],
            reason: $reason,
        );
    }

    public function statistics(VendorProfile $vendor): array
    {
        $vendorId = $vendor->user_id;

        return [
            'product_count' => $vendor->user->products()->count(),
            'published_product_count' => $vendor->user->products()->where('status', 'published')->count(),
            'order_count' => OrderItem::where('vendor_id', $vendorId)->distinct('order_id')->count('order_id'),
            'revenue' => (float) OrderItem::where('vendor_id', $vendorId)->sum(DB::raw('quantity * unit_price')),
        ];
    }
}
