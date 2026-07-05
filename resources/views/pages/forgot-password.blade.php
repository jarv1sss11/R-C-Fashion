<x-layouts.app title="Forgot Password — R&C Fashion">
    <x-navbar variant="minimal" />

    <main class="auth">
        <div class="container auth-inner">
            <x-auth-heading
                :lines="['Forgot Your', 'Password?']"
                subtitle="Enter your email and we'll send you a link to reset it."
                :divider="true"
            />

            <x-flash-status />

            <form method="POST" action="{{ route('password.email') }}" class="auth-form">
                @csrf

                <x-input-field label="Email Address" type="email" name="email" icon="mail" placeholder="Enter your email" />

                <x-button type="submit" variant="primary" icon="arrow-right" class="btn-block">
                    Send Reset Link
                </x-button>
            </form>

            <x-divider-with-label label="OR" />

            <div class="auth-secondary">
                <p class="auth-secondary-text">
                    Remembered your password?
                    <a href="{{ route('login') }}" class="auth-form-link">Sign In</a>
                </p>
            </div>
        </div>

        <x-auth-showcase />
    </main>
</x-layouts.app>
