<?php

namespace App\Http\Middleware;

use App\Services\Admin\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lightweight, settings-driven maintenance mode — deliberately not Laravel's
 * built-in `php artisan down`, which would also lock administrators out of
 * /admin (there's no --secret configured). Admins can always sign in and
 * manage the site; only buyers/vendors see the maintenance page.
 */
class CheckMaintenanceMode
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->role === 'admin') {
            return $next($request);
        }

        if ($this->settings->maintenanceMode()) {
            abort(503, 'This site is temporarily down for maintenance. Please check back soon.');
        }

        return $next($request);
    }
}
