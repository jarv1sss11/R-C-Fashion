@props(['active'])

<nav class="admin-sidebar">
    <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-link {{ $active === 'dashboard' ? 'is-active' : '' }}">Dashboard</a>
    <a href="{{ route('admin.users.index') }}" class="admin-sidebar-link {{ $active === 'users' ? 'is-active' : '' }}">Users</a>
    <a href="{{ route('admin.vendors.index') }}" class="admin-sidebar-link {{ $active === 'vendors' ? 'is-active' : '' }}">Vendors</a>
    <a href="{{ route('admin.products.index') }}" class="admin-sidebar-link {{ $active === 'products' ? 'is-active' : '' }}">Products</a>
    <a href="{{ route('admin.categories.index') }}" class="admin-sidebar-link {{ $active === 'categories' ? 'is-active' : '' }}">Categories</a>
    <a href="{{ route('admin.reports.index') }}" class="admin-sidebar-link {{ $active === 'reports' ? 'is-active' : '' }}">Reports</a>
    <a href="{{ route('admin.recommendation-analytics.index') }}" class="admin-sidebar-link {{ $active === 'recommendation-analytics' ? 'is-active' : '' }}">Recommendation Analytics</a>
    <a href="{{ route('admin.audit-logs.index') }}" class="admin-sidebar-link {{ $active === 'audit-logs' ? 'is-active' : '' }}">Audit Logs</a>
    <a href="{{ route('admin.settings.edit') }}" class="admin-sidebar-link {{ $active === 'settings' ? 'is-active' : '' }}">Settings</a>
    <a href="{{ route('admin.health.index') }}" class="admin-sidebar-link {{ $active === 'health' ? 'is-active' : '' }}">System Health</a>
</nav>
