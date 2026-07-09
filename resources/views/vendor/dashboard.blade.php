<x-layouts.app title="Vendor Dashboard — R&C Fashion">
    <x-navbar variant="full" />

    <main class="vendor">
        <div class="container vendor-inner">
            <x-vendor-sidebar active="dashboard" />

            <div class="vendor-content">
                <div class="vendor-heading-row">
                    <div>
                        <h1 class="vendor-heading">{{ $vendorProfile->store_name }}</h1>
                        <p class="vendor-subheading">Store Dashboard</p>
                    </div>
                    <x-status-badge :status="$vendorProfile->approval_status" />
                </div>

                <x-flash-status />

                <div class="vendor-stat-grid">
                    <div class="vendor-stat-card">
                        <span class="vendor-stat-value">{{ $totalProducts }}</span>
                        <span class="vendor-stat-label">Total Products</span>
                    </div>
                    <div class="vendor-stat-card">
                        <span class="vendor-stat-value">{{ $activeProducts }}</span>
                        <span class="vendor-stat-label">Active Products</span>
                    </div>
                    <div class="vendor-stat-card">
                        <span class="vendor-stat-value">{{ $outOfStockProducts }}</span>
                        <span class="vendor-stat-label">Out of Stock</span>
                    </div>
                    <a href="{{ route('vendor.orders.index') }}" class="vendor-stat-card vendor-stat-card--muted">
                        <span class="vendor-stat-value">{{ $pendingOrders }}</span>
                        <span class="vendor-stat-label">Pending Orders</span>
                    </a>
                    <div class="vendor-stat-card">
                        <span class="vendor-stat-value">{{ $activeDeliveries }}</span>
                        <span class="vendor-stat-label">Active Deliveries</span>
                    </div>
                </div>

                <h2 class="vendor-subheading vendor-subheading--section">Quick Actions</h2>

                <div class="vendor-quick-actions">
                    <a href="{{ route('vendor.products.create') }}" class="vendor-quick-action">
                        <span class="vendor-quick-action-title">Add a Product</span>
                        <span class="vendor-quick-action-desc">List a new item in your store</span>
                    </a>
                    <a href="{{ route('vendor.products.index') }}" class="vendor-quick-action">
                        <span class="vendor-quick-action-title">Manage Products</span>
                        <span class="vendor-quick-action-desc">Edit, restock, or remove listings</span>
                    </a>
                    <a href="{{ route('vendor.store.edit') }}" class="vendor-quick-action">
                        <span class="vendor-quick-action-title">Edit Store Profile</span>
                        <span class="vendor-quick-action-desc">Update your store's details</span>
                    </a>
                </div>
            </div>
        </div>
    </main>
</x-layouts.app>
