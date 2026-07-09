@php
    $notificationLabels = [
        'pending_vendor_approvals' => 'Pending Vendor Approvals',
        'pending_product_moderation' => 'Pending Product Moderation',
        'low_stock' => 'Low Stock Products',
        'out_of_stock' => 'Out-of-Stock Products',
        'suspended_vendors' => 'Suspended Vendors',
        'failed_recommendation_evaluations' => 'Failed Recommendation Evaluations',
        'pending_cod_payments' => 'Pending COD Payments to Confirm',
        'active_deliveries' => 'Active Deliveries in Progress',
    ];

    $healthBadges = ['green' => '🟢', 'yellow' => '🟡', 'red' => '🔴'];
@endphp

<x-layouts.app title="Dashboard — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="dashboard" />

            <div class="admin-content">
                <div class="admin-heading-row">
                    <h1 class="admin-heading">Dashboard</h1>
                    <span>{{ $healthBadges[$summary['health']['overall']] }} System {{ ucfirst($summary['health']['overall']) }}</span>
                </div>

                <x-flash-status />

                @if (!empty($notifications))
                    <div class="admin-notifications">
                        @foreach ($notifications as $key => $count)
                            <div class="admin-notification">
                                <span>{{ $notificationLabels[$key] }}</span>
                                <span class="admin-notification-count">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="admin-summary-grid">
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $summary['users']['total'] }}</span>
                        <span class="admin-summary-label">Total Users</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $summary['vendors']['total'] }}</span>
                        <span class="admin-summary-label">Total Vendors</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $summary['product_count'] }}</span>
                        <span class="admin-summary-label">Total Products</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $summary['orders']['total'] }}</span>
                        <span class="admin-summary-label">Total Orders</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">KES {{ number_format($summary['revenue']['total'], 2) }}</span>
                        <span class="admin-summary-label">Total Revenue</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $summary['recommendations']['ctr'] }}%</span>
                        <span class="admin-summary-label">Recommendation CTR</span>
                    </div>
                    <a href="{{ route('admin.payments.index') }}" class="admin-summary-card admin-summary-card--action">
                        <span class="admin-summary-value">{{ $summary['pending_cod_payments'] }}</span>
                        <span class="admin-summary-label">Pending COD Payments</span>
                    </a>
                    <a href="{{ route('admin.deliveries.index') }}" class="admin-summary-card admin-summary-card--action">
                        <span class="admin-summary-value">{{ $summary['active_deliveries'] }}</span>
                        <span class="admin-summary-label">Active Deliveries</span>
                    </a>
                </div>

                <div class="admin-chart-grid">
                    <x-bar-chart title="Orders per Month" :data="$summary['orders']['per_month']" />
                    <x-bar-chart title="Revenue per Month" :data="$summary['revenue']['per_month']" unit="KES " />
                    <x-bar-chart title="New Users per Month" :data="$summary['users']['new_per_month']" />
                    <x-bar-chart title="Vendor Registrations per Month" :data="$summary['vendors']['registrations_per_month']" />
                    <x-bar-chart title="Recommendation Clicks per Month" :data="$summary['recommendations']['clicks_per_month']" />
                    <x-bar-chart title="Best Selling Categories (Units)" :data="$summary['orders']['best_selling_categories']" />
                </div>
            </div>
        </div>
    </main>
</x-layouts.app>
