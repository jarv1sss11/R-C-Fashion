<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function edit(Request $request): View
    {
        return view('vendor.store.edit', [
            'vendorProfile' => $request->user()->vendorProfile,
            'counties' => config('kenya.counties'),
        ]);
    }

    public function update(UpdateStoreRequest $request): RedirectResponse
    {
        $vendorProfile = $request->user()->vendorProfile;

        $this->authorize('update', $vendorProfile);

        $validated = $request->safe()->except(['logo', 'banner']);

        if ($request->hasFile('logo')) {
            if ($vendorProfile->logo_path) {
                Storage::disk('public')->delete($vendorProfile->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('vendor-logos', 'public');
        }

        if ($request->hasFile('banner')) {
            if ($vendorProfile->banner_path) {
                Storage::disk('public')->delete($vendorProfile->banner_path);
            }

            $validated['banner_path'] = $request->file('banner')->store('vendor-banners', 'public');
        }

        $vendorProfile->update($validated);

        return back()->with('status', 'Store profile updated.');
    }
}
