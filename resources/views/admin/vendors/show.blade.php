<x-layouts.app :title="$vendor->store_name . ' — Admin'">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="vendors" />

            <div class="admin-content">
                <div class="admin-heading-row">
                    <div>
                        <h1 class="admin-heading">{{ $vendor->store_name }}</h1>
                        <p class="admin-subheading">{{ $vendor->user->name }} — {{ $vendor->user->email }}</p>
                    </div>
                    <div>
                        <x-status-badge :status="$vendor->approval_status" />
                        <x-status-badge :status="$vendor->user->status" />
                    </div>
                </div>

                <x-flash-status />

                <div class="vendor-stat-grid">
                    <div class="vendor-stat-card">
                        <span class="vendor-stat-value">{{ $stats['product_count'] }}</span>
                        <span class="vendor-stat-label">Total Products</span>
                    </div>
                    <div class="vendor-stat-card">
                        <span class="vendor-stat-value">{{ $stats['published_product_count'] }}</span>
                        <span class="vendor-stat-label">Published Products</span>
                    </div>
                    <div class="vendor-stat-card">
                        <span class="vendor-stat-value">{{ $stats['order_count'] }}</span>
                        <span class="vendor-stat-label">Orders</span>
                    </div>
                    <div class="vendor-stat-card vendor-stat-card--muted">
                        <span class="vendor-stat-value">KES {{ number_format($stats['revenue'], 2) }}</span>
                        <span class="vendor-stat-label">Total Revenue</span>
                    </div>
                </div>

                <p class="admin-subheading admin-subheading--spaced">
                    County: {{ $vendor->county ?? '—' }} · Phone: {{ $vendor->phone ?? '—' }}
                </p>
            </div>
        </div>
    </main>
</x-layouts.app>
