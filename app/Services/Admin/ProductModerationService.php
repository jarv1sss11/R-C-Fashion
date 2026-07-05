<?php

namespace App\Services\Admin;

use App\Enums\AuditAction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Moderation writes only ever touch `products.status` — the same enum the
 * Vendor Module already uses (draft/published/archived). "Hide" and "Archive"
 * are distinct audit-trail intents (temporary takedown vs. deliberate archive)
 * that both resolve to the `archived` value, since the brief forbids adding a
 * separate moderation_status column.
 */
class ProductModerationService
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function paginated(array $filters): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'vendor'])
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function approve(User $admin, Product $product, string $reason): void
    {
        $this->transition($admin, $product, 'published', AuditAction::ProductApproved, $reason);
    }

    public function reject(User $admin, Product $product, string $reason): void
    {
        $this->transition($admin, $product, 'draft', AuditAction::ProductRejected, $reason);
    }

    public function hide(User $admin, Product $product, string $reason): void
    {
        $this->transition($admin, $product, 'archived', AuditAction::ProductHidden, $reason);
    }

    public function archive(User $admin, Product $product, string $reason): void
    {
        $this->transition($admin, $product, 'archived', AuditAction::ProductArchived, $reason);
    }

    public function restore(User $admin, Product $product, string $reason): void
    {
        $this->transition($admin, $product, 'published', AuditAction::ProductRestored, $reason);
    }

    /**
     * Bulk actions reuse the exact same single-product transition below,
     * one row at a time — this is the same moderation logic and audit
     * trail as the single-item actions, just looped, not a new code path.
     *
     * @param  int[]  $productIds
     */
    public function bulkApprove(User $admin, array $productIds, string $reason): int
    {
        return $this->bulkTransition($admin, $productIds, 'approve', $reason);
    }

    /**
     * @param  int[]  $productIds
     */
    public function bulkArchive(User $admin, array $productIds, string $reason): int
    {
        return $this->bulkTransition($admin, $productIds, 'archive', $reason);
    }

    /**
     * "Bulk Delete" resolves to the same archive transition as the single-
     * item "Archive" action — `Product` deliberately has no SoftDeletes
     * (see Step 11's decision on `Category` being the only soft-deleted
     * model, to avoid a global-scope blast radius across every module that
     * queries `Product`), so there is no destructive delete to perform here.
     *
     * @param  int[]  $productIds
     */
    public function bulkDelete(User $admin, array $productIds, string $reason): int
    {
        return $this->bulkTransition($admin, $productIds, 'archive', $reason);
    }

    /**
     * @param  int[]  $productIds
     */
    private function bulkTransition(User $admin, array $productIds, string $action, string $reason): int
    {
        $products = Product::query()->whereIn('id', $productIds)->get();

        foreach ($products as $product) {
            $this->{$action}($admin, $product, $reason);
        }

        return $products->count();
    }

    private function transition(User $admin, Product $product, string $newStatus, AuditAction $action, string $reason): void
    {
        $oldStatus = $product->status;

        $product->update(['status' => $newStatus]);

        $this->auditLog->record(
            admin: $admin,
            action: $action,
            subject: $product,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus],
            reason: $reason,
        );
    }
}
