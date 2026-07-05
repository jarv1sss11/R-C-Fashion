<footer class="site-footer">
    <div class="container site-footer-inner">
        <div class="site-footer-brand">
            <span class="site-footer-wordmark">R&amp;C Fashion</span>
            <p class="site-footer-tagline">Quality, individuality, and Kenyan craftsmanship — curated for the modern shopper.</p>
        </div>

        <div class="site-footer-columns">
            <div class="site-footer-column">
                <span class="site-footer-heading">Shop</span>
                <a href="{{ gated_route(route('categories.show', 'men')) }}">Men</a>
                <a href="{{ gated_route(route('categories.show', 'women')) }}">Women</a>
                <a href="{{ gated_route(route('categories.show', 'kids')) }}">Kids</a>
                <a href="{{ gated_route(route('categories.show', 'sports')) }}">Sports</a>
                <a href="{{ gated_route(route('categories.show', 'accessories')) }}">Accessories</a>
            </div>

            <div class="site-footer-column">
                <span class="site-footer-heading">Company</span>
                <a href="{{ gated_route(route('products.index')) }}">Browse Catalogue</a>
                <a href="{{ gated_route(route('recommendations.index')) }}">Recommendations</a>
                @guest
                    <a href="{{ route('register') }}">Sell on R&amp;C</a>
                @endguest
            </div>

            <div class="site-footer-column">
                <span class="site-footer-heading">Account</span>
                @auth
                    <a href="{{ route('orders.index') }}">My Orders</a>
                    <a href="{{ route('account.edit') }}">My Profile</a>
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}">Create Account</a>
                @endauth
            </div>
        </div>
    </div>

    <div class="site-footer-bottom">
        <div class="container site-footer-bottom-inner">
            <span>&copy; {{ now()->year }} R&amp;C Fashion. All rights reserved.</span>
            <span>Made for Kenyan fashion shopping.</span>
        </div>
    </div>
</footer>
