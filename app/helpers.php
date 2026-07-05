<?php

if (! function_exists('gated_route')) {
    /**
     * Destination for nav items/CTAs that may or may not have a real page yet.
     * Guests are always sent to Login (the discovery hook) regardless of
     * whether a real destination exists — that gate is a deliberate product
     * decision, not a placeholder. Authenticated users get the real
     * destination if one is passed, otherwise an honest "coming soon" page.
     */
    function gated_route(?string $authenticatedDestination = null): string
    {
        if (! auth()->check()) {
            return route('login');
        }

        return $authenticatedDestination ?? route('coming-soon');
    }
}

if (! function_exists('cart_count')) {
    /**
     * Total item count in the buyer's persistent database cart (Step 10 —
     * replaced the Step 8 session-based version). Only ever called for
     * authenticated users (see navbar.blade.php's `auth()->check()` guard).
     */
    function cart_count(): int
    {
        return (int) (auth()->user()?->cart?->items()->sum('quantity') ?? 0);
    }
}
