<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\Admin\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function edit(): View
    {
        return view('admin.settings', [
            'settings' => $this->settings->current(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settings->update($request->user(), $request->validated());

        return back()->with('status', 'Settings updated.');
    }
}
