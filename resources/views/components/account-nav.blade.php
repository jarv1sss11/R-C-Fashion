@props(['active'])

<nav class="account-nav">
    <a href="{{ route('account.edit') }}" class="account-nav-link {{ $active === 'profile' ? 'is-active' : '' }}">Profile</a>
    <a href="{{ route('addresses.index') }}" class="account-nav-link {{ $active === 'addresses' ? 'is-active' : '' }}">Addresses</a>
    <a href="{{ route('wishlist.index') }}" class="account-nav-link {{ $active === 'wishlist' ? 'is-active' : '' }}">Wishlist</a>
    <a href="{{ route('orders.index') }}" class="account-nav-link {{ $active === 'orders' ? 'is-active' : '' }}">Orders &amp; Payments</a>
</nav>
