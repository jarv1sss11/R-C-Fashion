<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $users)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => $this->users->paginated($request->only(['search', 'role', 'status'])),
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->users->suspend($request->user(), $user, $request->input('reason'));

        return back()->with('status', "\"{$user->name}\" suspended.");
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->users->activate($request->user(), $user);

        return back()->with('status', "\"{$user->name}\" activated.");
    }

    public function assignAdmin(Request $request, User $user): RedirectResponse
    {
        $this->users->assignAdmin($request->user(), $user);

        return back()->with('status', "\"{$user->name}\" is now an administrator.");
    }
}
