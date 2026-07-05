<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
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
        $rules = [
            'role' => ['required', 'in:buyer,vendor'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($this->input('role') === 'vendor') {
            $rules['store_name'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    /**
     * Rate-limited the same way as LoginRequest, checked before the normal
     * field validation runs (via prepareForValidation()) so a flood of
     * registration attempts is capped regardless of whether each one is
     * otherwise valid. Keyed by IP only, not email — unlike login/reset,
     * a registration attempt's email is a new candidate identity an
     * attacker could rotate per request, so it isn't a useful throttle key.
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
            'email' => "Too many registration attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate('register|'.$this->ip());
    }
}
