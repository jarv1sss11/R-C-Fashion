<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(string $token): View
    {
        return view('pages.reset-password', [
            'token' => $token,
            'email' => request('email', ''),
        ]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $request->resetPassword();

        return redirect()->route('login')->with('status', 'Your password has been reset. Sign in with your new password.');
    }
}
