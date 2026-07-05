<?php

namespace App\Services\Admin;

use App\Enums\AuditAction;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function paginated(): LengthAwarePaginator
    {
        return Category::withTrashed()
            ->with('parent')
            ->withCount('products')
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(20);
    }

    public function create(User $admin, array $data): Category
    {
        $category = Category::create([
            ...$data,
            'display_order' => $data['display_order'] ?? 0,
            'slug' => $this->uniqueSlug($data['name']),
        ]);

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::CategoryCreated,
            subject: $category,
            newValues: $category->only(['name', 'slug', 'parent_id', 'display_order']),
        );

        return $category;
    }

    public function update(User $admin, Category $category, array $data): Category
    {
        $oldValues = $category->only(['name', 'parent_id', 'display_order']);

        $category->update([
            ...$data,
            'display_order' => $data['display_order'] ?? 0,
        ]);

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::CategoryUpdated,
            subject: $category,
            oldValues: $oldValues,
            newValues: $category->only(['name', 'parent_id', 'display_order']),
        );

        return $category;
    }

    public function archive(User $admin, Category $category, ?string $reason = null): void
    {
        if ($category->products()->count() > 0) {
            throw ValidationException::withMessages([
                'category' => 'This category still has products assigned to it and cannot be archived.',
            ]);
        }

        if ($category->children()->count() > 0) {
            throw ValidationException::withMessages([
                'category' => 'This category still has subcategories and cannot be archived.',
            ]);
        }

        $category->delete();

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::CategoryArchived,
            subject: $category,
            reason: $reason,
        );
    }

    public function restore(User $admin, Category $category): void
    {
        $category->restore();

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::CategoryRestored,
            subject: $category,
        );
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Category::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
