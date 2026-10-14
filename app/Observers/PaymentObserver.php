<?php

namespace App\Observers;

use App\Mail\ReceiptMail;
use App\Models\Payment;
use App\Services\ReceiptPdfService;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        if (strtolower((string) $payment->status) !== 'successful') {
            return;
        }

        try {
            $payment->loadMissing([
                'customer',
                'sale.customer',
                'sale.orders',
            ]);

            $customer = $payment->customer ?? $payment->sale?->customer;
            $sourceOrder = $payment->sale?->orders?->first();

            $email = $customer?->email ?: $sourceOrder?->email;
            $customerName = $customer?->name
                ?: $sourceOrder?->customer_name
                ?: 'Customer';

            if (! $email) {
                return;
            }

            $pdf = ReceiptPdfService::render(
                receiptNo: $payment->receipt_no,
                date: optional($payment->created_at)->format('d M Y H:i') ?? now()->format('d M Y H:i'),
                customerName: $customerName,
                method: $payment->method,
                amount: (float) $payment->amount,
                reference: $payment->invoice_no,
            );

            Mail::to($email)->send(new ReceiptMail(
                $payment->receipt_no,
                (string) $payment->amount,
                $payment->method,
                $pdf,
                "receipt-{$payment->receipt_no}.pdf"
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
