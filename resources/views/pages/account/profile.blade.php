<x-layouts.app title="My Account — R&C Fashion">
    <x-navbar variant="full" />

    <main class="account">
        <div class="container account-inner">
            <h1 class="account-heading">My Account</h1>

            <x-account-nav active="profile" />

            <x-flash-status />

            <form method="POST" action="{{ route('account.update') }}" class="auth-form account-form">
                @csrf
                @method('PUT')

                <x-input-field label="Full Name" name="name" :value="$user->name" />
                <x-input-field label="Email Address" type="email" name="email" icon="mail" :value="$user->email" />
                <x-input-field label="Phone Number" type="tel" name="phone" :value="$user->phone" placeholder="Add a phone number" />

                <x-button type="submit" variant="primary" class="btn-block">Save Changes</x-button>
            </form>
        </div>
    </main>
</x-layouts.app>
