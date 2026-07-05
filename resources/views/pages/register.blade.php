<x-layouts.app title="Begin Your Fashion Journey — R&C Fashion">
    <x-navbar variant="minimal" />

    <main class="auth" data-registration data-initial-role="{{ old('role') }}">
        <div class="container auth-inner registration-inner">
            <x-auth-heading
                :lines="['Begin Your Fashion', 'Journey']"
                subtitle="Create your account and unlock a world of style, quality and exclusive collections."
            />

            <x-flash-status />

            <x-divider-with-label label="JOIN AS" />

            <div class="role-card-group">
                <x-role-card
                    role="buyer"
                    variant="light"
                    icon="shopping-bag"
                    title="I Want To Shop"
                    sublabel="Shop the best outfits and collections"
                />
                <x-role-card
                    role="vendor"
                    variant="dark"
                    icon="storefront"
                    title="I Want To Sell"
                    sublabel="Start your store and reach thousands"
                />
            </div>

            <div class="accordion-panel" data-role-panel="buyer">
                <div class="accordion-panel-inner">
                    <form method="POST" action="{{ route('register.store') }}" class="auth-form">
                        @csrf
                        <input type="hidden" name="role" value="buyer">

                        <x-input-field label="Full Name" name="name" id="buyer-name" placeholder="Enter your full name" />
                        <x-input-field label="Email Address" type="email" name="email" id="buyer-email" icon="mail" placeholder="Enter your email" />
                        <x-input-field label="Phone Number" type="tel" name="phone" id="buyer-phone" placeholder="Enter your phone number" />
                        <x-input-field label="Password" type="password" name="password" id="buyer-password" placeholder="Create a password" />
                        <x-input-field label="Confirm Password" type="password" name="password_confirmation" id="buyer-password-confirmation" placeholder="Re-enter your password" />

                        <x-button type="submit" variant="primary" icon="arrow-right" class="btn-block">
                            Create Buyer Account
                        </x-button>
                    </form>
                </div>
            </div>

            <div class="accordion-panel" data-role-panel="vendor">
                <div class="accordion-panel-inner">
                    <form method="POST" action="{{ route('register.store') }}" class="auth-form">
                        @csrf
                        <input type="hidden" name="role" value="vendor">

                        <x-input-field label="Full Name" name="name" id="vendor-name" placeholder="Enter your full name" />
                        <x-input-field label="Email Address" type="email" name="email" id="vendor-email" icon="mail" placeholder="Enter your email" />
                        <x-input-field label="Phone Number" type="tel" name="phone" id="vendor-phone" placeholder="Enter your phone number" />
                        <x-input-field label="Store Name" name="store_name" id="vendor-store-name" placeholder="Enter your store name" />
                        <x-input-field label="Password" type="password" name="password" id="vendor-password" placeholder="Create a password" />
                        <x-input-field label="Confirm Password" type="password" name="password_confirmation" id="vendor-password-confirmation" placeholder="Re-enter your password" />

                        <x-button type="submit" variant="primary" icon="arrow-right" class="btn-block">
                            Create Vendor Account
                        </x-button>
                    </form>
                </div>
            </div>

            <div class="registration-signin">
                <p class="registration-signin-label">ALREADY HAVE AN ACCOUNT?</p>
                <a href="{{ route('login') }}" class="auth-form-link">Sign in</a>
            </div>
        </div>

        <x-auth-showcase />
    </main>
</x-layouts.app>
