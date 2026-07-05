@props([
    'variant' => 'full',
])

<header class="navbar">
    <div class="container navbar-inner">
        <a href="{{ route('home') }}" class="navbar-brand">
            <span class="navbar-wordmark">R&amp;C Fashion</span>
        </a>

        @if ($variant === 'full')
            <button type="button" class="navbar-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="navMenu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav class="navbar-menu" id="navMenu">
                <x-nav-link :href="route('home')" :active="request()->routeIs('home')">Home</x-nav-link>
                <x-nav-link :href="gated_route(route('categories.show', 'men'))" :active="request()->routeIs('categories.show') && request()->route('category')?->slug === 'men'">Men</x-nav-link>
                <x-nav-link :href="gated_route(route('categories.show', 'women'))" :active="request()->routeIs('categories.show') && request()->route('category')?->slug === 'women'">Women</x-nav-link>
                <x-nav-link :href="gated_route(route('categories.show', 'kids'))" :active="request()->routeIs('categories.show') && request()->route('category')?->slug === 'kids'">Kids</x-nav-link>
                <x-nav-link :href="gated_route(route('categories.show', 'sports'))" :active="request()->routeIs('categories.show') && request()->route('category')?->slug === 'sports'">Sports</x-nav-link>
                <x-nav-link :href="gated_route(route('categories.show', 'accessories'))" :active="request()->routeIs('categories.show') && request()->route('category')?->slug === 'accessories'">Accessories</x-nav-link>
                <x-nav-link :href="gated_route(route('recommendations.index'))" :active="request()->routeIs('recommendations.*')">Recommendations</x-nav-link>

                <span class="navbar-divider" aria-hidden="true"></span>

                <x-nav-link :href="gated_route(route('cart.index'))" :active="request()->routeIs('cart.*')">Cart ({{ auth()->check() ? cart_count() : 0 }})</x-nav-link>

                @auth
                    <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">My Orders</x-nav-link>
                    @if (auth()->user()->role === 'vendor')
                        <x-nav-link :href="route('vendor.dashboard')" :active="request()->routeIs('vendor.*')">Dashboard</x-nav-link>
                    @endif
                    @if (auth()->user()->role === 'admin')
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">Admin</x-nav-link>
                    @endif
                    <a href="{{ route('account.edit') }}" class="navbar-user">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="navbar-logout-form">
                        @csrf
                        <button type="submit" class="nav-link navbar-logout">Logout</button>
                    </form>
                @else
                    <x-nav-link :href="route('login')">Profile</x-nav-link>
                @endauth
            </nav>
        @else
            <a href="{{ route('home') }}" class="navbar-back">
                <x-icon name="arrow-right" class="navbar-back-icon" />
                <span>Back to Home</span>
            </a>
        @endif
    </div>
</header>
