<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('pages.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
                'password' => $validated['password'],
            ]);

            if ($validated['role'] === 'vendor') {
                // No approval workflow enforced yet — approval_status defaults to
                // 'pending' at the schema level, but nothing currently checks it
                // (see DATABASE_BLUEPRINT.md's vendor_profiles decision fork).
                VendorProfile::create([
                    'user_id' => $user->id,
                    'store_name' => $validated['store_name'],
                    'store_slug' => $this->uniqueStoreSlug($validated['store_name']),
                ]);
            }

            return $user;
        });

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('home')->with('status', "Welcome to R&C Fashion, {$user->name}!");
    }

    private function uniqueStoreSlug(string $storeName): string
    {
        $base = Str::slug($storeName);
        $slug = $base;
        $suffix = 1;

        while (VendorProfile::where('store_slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
