<?php

namespace App\Services\Admin;

use App\Enums\AuditAction;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Config;

/**
 * A deliberately small key-value store — not a generic settings framework.
 * Every key here has one job: overlay onto a specific config() value at boot
 * (via applyOverlay(), called from AppServiceProvider::boot()) so the rest
 * of the app — including the Recommendation Engine — keeps reading
 * config() exactly as before and never touches this table directly.
 */
class SettingsService
{
    private const KEYS = [
        'site_name' => 'app.name',
        'delivery_cost_standard' => 'shipping.delivery_costs.standard',
        'delivery_cost_express' => 'shipping.delivery_costs.express',
        'recommendation_weight_content' => 'recommendation.weights.content',
        'recommendation_weight_collaborative' => 'recommendation.weights.collaborative',
        'recommendation_weight_popularity' => 'recommendation.weights.popularity',
    ];

    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function current(): array
    {
        return [
            'site_name' => config('app.name'),
            'delivery_cost_standard' => config('shipping.delivery_costs.standard'),
            'delivery_cost_express' => config('shipping.delivery_costs.express'),
            'recommendation_weight_content' => config('recommendation.weights.content'),
            'recommendation_weight_collaborative' => config('recommendation.weights.collaborative'),
            'recommendation_weight_popularity' => config('recommendation.weights.popularity'),
            'maintenance_mode' => $this->maintenanceMode(),
        ];
    }

    public function maintenanceMode(): bool
    {
        return (bool) (Setting::where('key', 'maintenance_mode')->value('value') ?? false);
    }

    public function update(User $admin, array $data): void
    {
        $old = $this->current();

        foreach (self::KEYS as $key => $configPath) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            Setting::updateOrCreate(['key' => $key], ['value' => json_encode($data[$key])]);
            Config::set($configPath, $data[$key]);
        }

        Setting::updateOrCreate(
            ['key' => 'maintenance_mode'],
            ['value' => json_encode((bool) ($data['maintenance_mode'] ?? false))],
        );

        $this->auditLog->record(
            admin: $admin,
            action: AuditAction::SettingsUpdated,
            oldValues: $old,
            newValues: $this->current(),
        );
    }

    /**
     * Called once from AppServiceProvider::boot() so every request reflects
     * whatever was last saved, regardless of which process saved it.
     */
    public function applyOverlay(): void
    {
        $settings = Setting::whereIn('key', array_keys(self::KEYS))->pluck('value', 'key');

        foreach (self::KEYS as $key => $configPath) {
            if ($settings->has($key)) {
                Config::set($configPath, json_decode($settings->get($key), true));
            }
        }
    }
}
