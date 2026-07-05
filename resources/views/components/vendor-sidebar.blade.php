@props(['active'])

<nav class="vendor-sidebar">
    <a href="{{ route('vendor.dashboard') }}" class="vendor-sidebar-link {{ $active === 'dashboard' ? 'is-active' : '' }}">Dashboard</a>
    <a href="{{ route('vendor.products.index') }}" class="vendor-sidebar-link {{ $active === 'products' ? 'is-active' : '' }}">Products</a>
    <a href="{{ route('vendor.orders.index') }}" class="vendor-sidebar-link {{ $active === 'orders' ? 'is-active' : '' }}">Orders</a>
    <a href="{{ route('vendor.store.edit') }}" class="vendor-sidebar-link {{ $active === 'store' ? 'is-active' : '' }}">Store</a>
    <a href="{{ route('account.edit') }}" class="vendor-sidebar-link {{ $active === 'profile' ? 'is-active' : '' }}">Profile</a>
</nav>
