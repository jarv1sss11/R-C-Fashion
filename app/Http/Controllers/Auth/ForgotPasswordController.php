<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('pages.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $request->sendResetLink();

        return back()->with('status', 'If an account exists for that email address, we\'ve sent a link to reset your password.');
    }
}
