@php
    $entityLabel = function ($log) {
        if (! $log->auditable) {
            return '—';
        }

        $name = match (true) {
            $log->auditable instanceof \App\Models\User => $log->auditable->name,
            $log->auditable instanceof \App\Models\VendorProfile => $log->auditable->store_name,
            default => $log->auditable->name,
        };

        return class_basename($log->auditable) . ': ' . $name;
    };

    $changesSummary = function ($log) {
        if (! $log->new_values) {
            return '—';
        }

        return collect($log->new_values)
            ->map(fn ($value, $key) => "{$key}: " . ($log->old_values[$key] ?? '—') . ' → ' . $value)
            ->implode(', ');
    };
@endphp

<x-layouts.app title="Audit Logs — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="audit-logs" />

            <div class="admin-content">
                <h1 class="admin-heading">Audit Logs</h1>

                <x-flash-status />

                <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="admin-filter-form">
                    <select name="admin_id" class="input-field-input">
                        <option value="">All Administrators</option>
                        @foreach ($admins as $admin)
                            <option value="{{ $admin->id }}" @selected((string) ($filters['admin_id'] ?? '') === (string) $admin->id)>{{ $admin->name }}</option>
                        @endforeach
                    </select>

                    <select name="action" class="input-field-input">
                        <option value="">All Actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action->value }}" @selected(($filters['action'] ?? '') === $action->value)>{{ $action->label() }}</option>
                        @endforeach
                    </select>

                    <select name="auditable_type" class="input-field-input">
                        <option value="">All Entities</option>
                        @foreach ($entityTypes as $label => $class)
                            <option value="{{ $class }}" @selected(($filters['auditable_type'] ?? '') === $class)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input-field-input">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-field-input">

                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                @if ($logs->isEmpty())
                    <x-empty-state title="No audit log entries" message="Try adjusting your filters." />
                @else
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Administrator</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Changes</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $log->admin->name ?? 'Deleted Admin' }}</td>
                                    <td>{{ \App\Enums\AuditAction::from($log->action)->label() }}</td>
                                    <td>{{ $entityLabel($log) }}</td>
                                    <td>{{ $changesSummary($log) }}</td>
                                    <td>{{ $log->reason ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <x-pagination :paginator="$logs" />
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
