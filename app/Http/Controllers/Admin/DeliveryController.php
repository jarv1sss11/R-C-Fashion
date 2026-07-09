<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RiderAssignedMail;
use App\Mail\VendorRiderAssignedMail;
use App\Models\DeliveryAssignment;
use App\Models\Order;
use App\Models\Rider;
use App\Models\VendorProfile;
use App\Notifications\RiderAssignedNotification;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(Request $request): View
    {
        $orders = Order::with(['user', 'payment', 'deliveryAssignment.rider'])
            ->whereDoesntHave('deliveryAssignment')
            ->whereIn('payment_status', ['paid'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $riders = Rider::where('status', 'active')->where('available', true)->get();

        return view('admin.deliveries.index', compact('orders', 'riders'));
    }

    public function assign(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'rider_id'            => ['required', 'exists:riders,id'],
            'estimated_delivery'  => ['nullable', 'date', 'after:now'],
            'notes'               => ['nullable', 'string', 'max:500'],
        ]);

        if ($order->deliveryAssignment) {
            return back()->with('error', 'A rider is already assigned to this order.');
        }

        $rider = Rider::findOrFail($request->rider_id);

        $assignment = DeliveryAssignment::create([
            'order_id'            => $order->id,
            'rider_id'            => $rider->id,
            'assigned_by'         => $request->user()->id,
            'estimated_delivery'  => $request->estimated_delivery,
            'notes'               => $request->notes,
        ]);

        $order->update(['delivery_status' => 'rider_assigned']);

        $this->audit->log('rider_assigned', $order, [
            'rider_id'   => $rider->id,
            'rider_name' => $rider->name,
        ], adminId: $request->user()->id);

        // Notify customer (database + email)
        try {
            $order->loadMissing(['user', 'items']);
            $assignment->load('rider');

            $order->user->notify(new RiderAssignedNotification($assignment));

            Mail::to($order->user->email)
                ->send(new RiderAssignedMail($order, $assignment));

            Log::info('Rider assigned email sent', [
                'order_id' => $order->id,
                'mailable' => RiderAssignedMail::class,
            ]);

            $this->sendVendorRiderNotifications($order, $assignment);
        } catch (\Throwable $e) {
            // notification failure must not break the assignment
            Log::error('Rider assignment notification/email failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'mailable' => RiderAssignedMail::class,
                'exception' => $e->getMessage(),
            ]);
        }

        return back()->with('status', "Rider {$rider->name} assigned to order {$order->order_number}.");
    }

    public function updateStatus(Request $request, DeliveryAssignment $assignment): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:assigned,picked_up,delivered'],
        ]);

        $old = $assignment->status;
        $assignment->update(['status' => $request->status]);

        $deliveryStatus = match ($request->status) {
            'picked_up' => 'out_for_delivery',
            'delivered' => 'delivered',
            default     => 'rider_assigned',
        };

        $assignment->order->update(['delivery_status' => $deliveryStatus]);

        $this->audit->log('delivery_status_updated', $assignment->order, [
            'delivery_status' => $deliveryStatus,
        ], ['delivery_status' => $old], adminId: $request->user()->id);

        return back()->with('status', "Delivery status updated to {$request->status}.");
    }

    private function sendVendorRiderNotifications(Order $order, DeliveryAssignment $assignment): void
    {
        $byVendor = $order->items->groupBy('vendor_id');

        foreach ($byVendor as $vendorId => $items) {
            $profile = VendorProfile::where('user_id', $vendorId)->first();
            if (! $profile || ! $profile->email) {
                continue;
            }

            Mail::to($profile->email)->send(
                new VendorRiderAssignedMail($order, $assignment, $items, $profile->store_name ?? 'Your Store')
            );
        }
    }
}
