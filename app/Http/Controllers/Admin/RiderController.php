<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiderRequest;
use App\Models\Rider;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiderController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(Request $request): View
    {
        $riders = Rider::query()
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('search'), fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.riders.index', compact('riders'));
    }

    public function create(): View
    {
        return view('admin.riders.create');
    }

    public function store(StoreRiderRequest $request): RedirectResponse
    {
        $rider = Rider::create($request->validated() + ['available' => $request->boolean('available', true)]);

        $this->audit->log('rider_created', $rider, $request->validated(), adminId: $request->user()->id);

        return redirect()->route('admin.riders.index')->with('status', "Rider {$rider->name} created.");
    }

    public function edit(Rider $rider): View
    {
        return view('admin.riders.edit', compact('rider'));
    }

    public function update(StoreRiderRequest $request, Rider $rider): RedirectResponse
    {
        $old = $rider->only(array_keys($request->validated()));
        $rider->update($request->validated() + ['available' => $request->boolean('available')]);

        $this->audit->log('rider_updated', $rider, $request->validated(), $old, adminId: $request->user()->id);

        return redirect()->route('admin.riders.index')->with('status', "Rider {$rider->name} updated.");
    }

    public function destroy(Rider $rider): RedirectResponse
    {
        $name = $rider->name;
        $rider->delete();

        return redirect()->route('admin.riders.index')->with('status', "Rider {$name} deleted.");
    }
}
