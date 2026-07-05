<x-layouts.app title="Settings — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="settings" />

            <div class="admin-content">
                <h1 class="admin-heading">Settings</h1>

                <x-flash-status />

                <form method="POST" action="{{ route('admin.settings.update') }}" class="auth-form admin-form">
                    @csrf
                    @method('PUT')

                    <x-input-field label="Site Name" name="site_name" :value="$settings['site_name']" />

                    <h2 class="admin-subheading--section">Delivery Costs (KES)</h2>
                    <x-input-field label="Standard Delivery" type="number" name="delivery_cost_standard" :value="$settings['delivery_cost_standard']" step="0.01" min="0" />
                    <x-input-field label="Express Delivery" type="number" name="delivery_cost_express" :value="$settings['delivery_cost_express']" step="0.01" min="0" />

                    <h2 class="admin-subheading--section">Recommendation Weights</h2>
                    <p class="admin-subheading">Base weights for the hybrid blend. The engine redistributes these proportionally when an algorithm has no signal for a user — no code change needed here.</p>
                    <x-input-field label="Content-Based Weight" type="number" name="recommendation_weight_content" :value="$settings['recommendation_weight_content']" step="0.01" min="0" max="1" />
                    <x-input-field label="Collaborative Weight" type="number" name="recommendation_weight_collaborative" :value="$settings['recommendation_weight_collaborative']" step="0.01" min="0" max="1" />
                    <x-input-field label="Popularity Weight" type="number" name="recommendation_weight_popularity" :value="$settings['recommendation_weight_popularity']" step="0.01" min="0" max="1" />

                    <h2 class="admin-subheading--section">Maintenance Mode</h2>
                    <x-checkbox label="Show a maintenance page to buyers and vendors (administrators are unaffected)" name="maintenance_mode" :checked="$settings['maintenance_mode']" />

                    <x-button type="submit" variant="primary" class="btn-block">Save Settings</x-button>
                </form>
            </div>
        </div>
    </main>
</x-layouts.app>
