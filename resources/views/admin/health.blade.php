@php
    $badge = fn (string $status) => $status === 'ok' ? '🟢' : '🔴';
    $queueBadge = $checks['queue']['failed'] > 0 ? '🟡' : '🟢';
@endphp

<x-layouts.app title="System Health — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="health" />

            <div class="admin-content">
                <h1 class="admin-heading">System Health</h1>
                <p class="admin-subheading">Read-only — reflects current system state at page load.</p>

                <x-flash-status />

                <div class="admin-summary-grid">
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $badge($checks['database']['status']) }}</span>
                        <span class="admin-summary-label">Database</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $badge($checks['cache']['status']) }}</span>
                        <span class="admin-summary-label">Cache</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $badge($checks['storage']['status']) }}</span>
                        <span class="admin-summary-label">Storage</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $queueBadge }} {{ $checks['queue']['pending'] }} pending</span>
                        <span class="admin-summary-label">Queue</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $checks['queue']['failed'] }}</span>
                        <span class="admin-summary-label">Failed Jobs</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $checks['app_version'] }}</span>
                        <span class="admin-summary-label">Application Version</span>
                    </div>
                </div>

                <h2 class="admin-subheading--section">Data Overview</h2>
                <div class="admin-summary-grid">
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $counts['users'] }}</span>
                        <span class="admin-summary-label">Users</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $counts['vendors'] }}</span>
                        <span class="admin-summary-label">Vendors</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $counts['products'] }}</span>
                        <span class="admin-summary-label">Products</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $counts['orders'] }}</span>
                        <span class="admin-summary-label">Orders</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $counts['recommendations'] }}</span>
                        <span class="admin-summary-label">Recommendations</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-layouts.app>
