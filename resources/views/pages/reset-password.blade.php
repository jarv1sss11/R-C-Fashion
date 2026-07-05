<x-layouts.app title="Reset Password — R&C Fashion">
    <x-navbar variant="minimal" />

    <main class="auth">
        <div class="container auth-inner">
            <x-auth-heading
                :lines="['Reset Your', 'Password']"
                subtitle="Choose a new password for your account."
                :divider="true"
            />

            <x-flash-status />

            <form method="POST" action="{{ route('password.update') }}" class="auth-form">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <x-input-field label="Email Address" type="email" name="email" icon="mail" :value="$email" placeholder="Enter your email" />
                <x-input-field label="New Password" type="password" name="password" placeholder="Enter your new password" />
                <x-input-field label="Confirm New Password" type="password" name="password_confirmation" placeholder="Re-enter your new password" />

                <x-button type="submit" variant="primary" icon="arrow-right" class="btn-block">
                    Reset Password
                </x-button>
            </form>
        </div>

        <x-auth-showcase />
    </main>
</x-layouts.app>
