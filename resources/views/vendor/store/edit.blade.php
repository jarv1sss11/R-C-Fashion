<x-layouts.app title="Store Profile — R&C Fashion">
    <x-navbar variant="full" />

    <main class="vendor">
        <div class="container vendor-inner">
            <x-vendor-sidebar active="store" />

            <div class="vendor-content">
                <h1 class="vendor-heading">Store Profile</h1>

                <x-flash-status />

                @if ($vendorProfile->banner_url)
                    <img src="{{ $vendorProfile->banner_url }}" alt="{{ $vendorProfile->store_name }} banner" class="vendor-banner-preview">
                @endif

                @if ($vendorProfile->logo_url)
                    <img src="{{ $vendorProfile->logo_url }}" alt="{{ $vendorProfile->store_name }} logo" class="vendor-logo-preview">
                @endif

                <form method="POST" action="{{ route('vendor.store.update') }}" enctype="multipart/form-data" class="auth-form vendor-form">
                    @csrf
                    @method('PUT')

                    <x-input-field label="Store Name" name="store_name" :value="$vendorProfile->store_name" />

                    <x-textarea-field label="Store Description" name="description" :value="$vendorProfile->description" placeholder="Tell buyers about your store" />

                    <x-input-field label="Phone Number" type="tel" name="phone" :value="$vendorProfile->phone" placeholder="Enter a contact number" />

                    <x-input-field label="Email Address" type="email" name="email" icon="mail" :value="$vendorProfile->email" placeholder="Enter a contact email" />

                    <x-select-field
                        label="County / Location"
                        name="county"
                        :options="array_combine($counties, $counties)"
                        :value="$vendorProfile->county"
                        placeholder="Select a county"
                    />

                    <x-file-field label="Store Logo" name="logo" />

                    <x-file-field label="Store Banner" name="banner" />

                    <x-button type="submit" variant="primary" class="btn-block">Save Changes</x-button>
                </form>
            </div>
        </div>
    </main>
</x-layouts.app>
