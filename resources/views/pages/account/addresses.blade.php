<x-layouts.app title="My Addresses — R&C Fashion">
    <x-navbar variant="full" />

    <main class="account">
        <div class="container account-inner">
            <h1 class="account-heading">My Account</h1>

            <x-account-nav active="addresses" />

            <x-flash-status />

            @if ($addresses->isEmpty())
                <p class="account-empty">You haven't added an address yet.</p>
            @else
                <ul class="address-list">
                    @foreach ($addresses as $address)
                        <li class="address-card">
                            <div class="address-card-body">
                                <div class="address-card-heading">
                                    <span class="address-card-label">{{ $address->label ?: 'Address' }}</span>
                                    @if ($address->is_default)
                                        <span class="address-card-badge">Default</span>
                                    @endif
                                </div>
                                <p class="address-card-line">{{ $address->line1 }}, {{ $address->city }}</p>
                                @if ($address->phone)
                                    <p class="address-card-line">{{ $address->phone }}</p>
                                @endif
                            </div>
                            <div class="address-card-actions">
                                @unless ($address->is_default)
                                    <form method="POST" action="{{ route('addresses.default', $address) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="address-card-action">Set as Default</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('addresses.destroy', $address) }}" data-confirm="Remove this address?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="address-card-action address-card-action--danger">Remove</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <h2 class="account-subheading">Add a New Address</h2>

            <form method="POST" action="{{ route('addresses.store') }}" class="auth-form account-form">
                @csrf

                <x-input-field label="Label" name="label" placeholder="e.g. Home, Office" />
                <x-input-field label="Street Address" name="line1" placeholder="Enter your street address" />
                <x-input-field label="City" name="city" placeholder="Enter your city" />
                <x-input-field label="Phone Number" type="tel" name="phone" placeholder="Enter a contact number" />

                <x-checkbox label="Set as default address" name="is_default" />

                <x-button type="submit" variant="primary" class="btn-block">Add Address</x-button>
            </form>
        </div>
    </main>
</x-layouts.app>
