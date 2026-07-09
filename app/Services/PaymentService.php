<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class PaymentService
{
    public function createForOrder(Order $order): Payment
    {
        $isPaid = in_array($order->payment_method, ['mpesa', 'mock_card'], true);

        return Payment::create([
            'order_id'          => $order->id,
            'payment_method'    => $order->payment_method,
            'payment_reference' => $isPaid ? $this->generateReference($order) : null,
            'amount'            => $order->total,
            'status'            => $isPaid ? 'paid' : 'pending',
            'paid_at'           => $isPaid ? now() : null,
            'meta'              => $this->buildMeta($order),
        ]);
    }

    public function confirmCod(Payment $payment): void
    {
        $payment->update([
            'status'            => 'paid',
            'payment_reference' => $this->generateReceiptNumber(),
            'paid_at'           => now(),
        ]);

        $payment->order->update(['payment_status' => 'paid']);
    }

    public function generateReceiptNumber(): string
    {
        return 'RCP-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }

    private function generateReference(Order $order): string
    {
        if ($order->payment_method === 'mpesa') {
            return 'MP-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
        }

        return 'CARD-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
    }

    private function buildMeta(Order $order): array
    {
        $meta = ['simulated' => true];

        if ($order->payment_method === 'mpesa') {
            $meta['phone'] = $order->shipping_phone;
        }

        return $meta;
    }
}
