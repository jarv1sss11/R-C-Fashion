<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Complete the reset via Laravel's built-in password broker. Unlike
     * ForgotPasswordRequest, a failure here (invalid/expired token) is safe
     * to report directly — the visitor already holds a token from a real
     * emailed link, so there's no new account-existence information leaked
     * by saying it didn't work.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function resetPassword(): void
    {
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }
    }
}
