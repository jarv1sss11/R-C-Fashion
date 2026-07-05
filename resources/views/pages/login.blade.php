<x-layouts.app title="Continue Your Fashion Journey — R&C Fashion">
    <x-navbar variant="minimal" />

    <main class="auth">
        <div class="container auth-inner">
            <x-auth-heading
                :lines="['Continue Your Fashion', 'Journey']"
                subtitle="Sign in to your account and unlock a world of style."
                :divider="true"
            />

            <x-flash-status />

            <form method="POST" action="{{ route('login.store') }}" class="auth-form">
                @csrf

                <x-input-field label="Email Address" type="email" name="email" icon="mail" placeholder="Enter your email" />
                <x-input-field label="Password" type="password" name="password" placeholder="Enter your password" />

                <div class="auth-form-row">
                    <x-checkbox label="Remember me" name="remember" />
                    <a href="{{ route('password.request') }}" class="auth-form-link">Forgot Password?</a>
                </div>

                <x-button type="submit" variant="primary" icon="arrow-right" class="btn-block">
                    Sign In
                </x-button>
            </form>

            <x-divider-with-label label="OR" />

            <div class="auth-secondary">
                <p class="auth-secondary-text">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="auth-form-link">Create Account</a>
                </p>
                <x-button :href="route('register')" variant="outline" icon="person-plus" class="btn-block">
                    Create Account
                </x-button>
            </div>
        </div>

        <x-auth-showcase />
    </main>
</x-layouts.app>
