<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VendorModerationRequest;
use App\Models\VendorProfile;
use App\Services\Admin\VendorManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(private readonly VendorManagementService $vendors)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.vendors.index', [
            'vendors' => $this->vendors->paginated($request->only(['search', 'approval_status'])),
            'filters' => $request->only(['search', 'approval_status']),
        ]);
    }

    public function show(VendorProfile $vendor): View
    {
        return view('admin.vendors.show', [
            'vendor' => $vendor->load('user'),
            'stats' => $this->vendors->statistics($vendor),
        ]);
    }

    public function approve(VendorModerationRequest $request, VendorProfile $vendor): RedirectResponse
    {
        $this->vendors->approve($request->user(), $vendor, $request->validated('reason'));

        return back()->with('status', "\"{$vendor->store_name}\" approved.");
    }

    public function reject(VendorModerationRequest $request, VendorProfile $vendor): RedirectResponse
    {
        $this->vendors->reject($request->user(), $vendor, $request->validated('reason'));

        return back()->with('status', "\"{$vendor->store_name}\" rejected.");
    }

    public function suspend(VendorModerationRequest $request, VendorProfile $vendor): RedirectResponse
    {
        $this->vendors->suspend($request->user(), $vendor, $request->validated('reason'));

        return back()->with('status', "\"{$vendor->store_name}\" suspended.");
    }

    public function restore(VendorModerationRequest $request, VendorProfile $vendor): RedirectResponse
    {
        $this->vendors->restore($request->user(), $vendor, $request->validated('reason'));

        return back()->with('status', "\"{$vendor->store_name}\" restored.");
    }
}
