<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->ensureIsNotRateLimited();
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    /**
     * Send the reset link via Laravel's built-in password broker — reuses
     * the framework's own `password_reset_tokens` table and throttling,
     * nothing custom. `MAIL_MAILER=log` in this environment means the link
     * lands in `storage/logs/laravel.log` instead of a real inbox.
     *
     * Deliberately returns void, not the broker's status: this project's
     * established security convention (see LoginRequest) is to never let a
     * failure message reveal whether an email is registered, so an unknown
     * address is treated identically to a successful send — the controller
     * always shows the same generic confirmation.
     */
    public function sendResetLink(): void
    {
        Password::sendResetLink($this->only('email'));
    }

    /**
     * Rate-limited the same way as LoginRequest (same threshold, same
     * email+IP key), checked before the normal field validation runs (via
     * prepareForValidation()) so repeated reset-link requests against the
     * same email/IP are capped, matching Login's throttle behavior.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            RateLimiter::hit($this->throttleKey());

            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Too many password reset requests. Please try again in {$seconds} seconds.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
