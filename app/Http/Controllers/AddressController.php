<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.account.addresses', [
            'addresses' => $request->user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get(),
        ]);
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $makeDefault = $request->boolean('is_default') || ! $user->addresses()->exists();

        DB::transaction(function () use ($user, $validated, $makeDefault) {
            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $user->addresses()->create([
                ...$validated,
                'is_default' => $makeDefault,
            ]);
        });

        return back()->with('status', 'Address added.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->authorize('delete', $address);

        $wasDefault = $address->is_default;
        $user = $request->user();

        $address->delete();

        if ($wasDefault) {
            $user->addresses()->oldest('id')->first()?->update(['is_default' => true]);
        }

        return back()->with('status', 'Address removed.');
    }

    public function makeDefault(Request $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        DB::transaction(function () use ($request, $address) {
            $request->user()->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return back()->with('status', 'Default address updated.');
    }
}
