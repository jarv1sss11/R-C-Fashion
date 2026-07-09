<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentConfirmedMail;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentConfirmedNotification;
use App\Services\AuditLogService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly AuditLogService $audit,
    ) {}

    public function index(Request $request): View
    {
        $payments = Payment::with(['order.user'])
            ->when($request->input('method'), fn ($q, $v) => $q->where('payment_method', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function confirmCod(Payment $payment): RedirectResponse
    {
        abort_if($payment->payment_method !== 'cash_on_delivery', 403);
        abort_if($payment->status === 'paid', 422, 'Payment already confirmed.');

        $this->paymentService->confirmCod($payment);

        $this->audit->log('payment_confirmed', $payment->order, [
            'payment_method'    => 'cash_on_delivery',
            'payment_reference' => $payment->payment_reference,
            'amount'            => $payment->amount,
        ], adminId: request()->user()?->id);

        try {
            $payment->order->loadMissing('user');
            $payment->order->user->notify(new PaymentConfirmedNotification($payment->order, $payment));
            Mail::to($payment->order->user->email)
                ->send(new PaymentConfirmedMail($payment->order, $payment));
            Log::info('Payment confirmation email sent', [
                'order_id' => $payment->order->id,
                'mailable' => PaymentConfirmedMail::class,
            ]);
        } catch (\Throwable $e) {
            // notification/email failure must not break the confirmation action
            Log::error('COD payment confirmation notification/email failed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order->id,
                'order_number' => $payment->order->order_number,
                'mailable' => PaymentConfirmedMail::class,
                'exception' => $e->getMessage(),
            ]);
        }

        return back()->with('status', "COD payment for order {$payment->order->order_number} confirmed.");
    }
}
