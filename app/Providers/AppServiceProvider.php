<?php

namespace App\Providers;

use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\VendorProfile;
use App\Observers\ProductObserver;
use App\Policies\CartPolicy;
use App\Policies\OrderPolicy;
use App\Policies\StorePolicy;
use App\Services\Admin\SettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keep search_index in sync whenever a product is created or updated.
        Product::observe(ProductObserver::class);

        // StorePolicy doesn't follow the {Model}Policy auto-discovery convention
        // (VendorProfile → StorePolicy, not VendorProfilePolicy), so it needs
        // explicit registration.
        Gate::policy(VendorProfile::class, StorePolicy::class);

        // Same situation: CartItem → CartPolicy, not CartItemPolicy.
        Gate::policy(CartItem::class, CartPolicy::class);

        // OrderItem → OrderPolicy, not OrderItemPolicy (Order → OrderPolicy
        // already auto-discovers correctly and needs no registration).
        Gate::policy(OrderItem::class, OrderPolicy::class);

        // Overlay any admin-saved Settings onto config() before the rest of
        // the app reads it. Guarded for artisan commands that run before the
        // settings table exists (e.g. a fresh `migrate` on first install).
        if (Schema::hasTable('settings')) {
            $this->app->make(SettingsService::class)->applyOverlay();
        }
    }
}
