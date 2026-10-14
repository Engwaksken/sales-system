<?php

namespace App\Observers;

use App\Mail\ReceiptMail;
use App\Models\OrderPaymentReceipt;
use App\Services\ReceiptPdfService;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderPaymentReceiptObserver
{
    public function created(OrderPaymentReceipt $receipt): void
    {
        try {
            if (! $receipt->email) {
                return;
            }

            $pdf = ReceiptPdfService::render(
                receiptNo: $receipt->receipt_number,
                date: optional($receipt->created_at)->format('d M Y H:i') ?? now()->format('d M Y H:i'),
                customerName: $receipt->customer_name,
                method: $receipt->payment_method,
                amount: (float) $receipt->amount_paid,
                reference: $receipt->gateway_reference,
            );

            Mail::to($receipt->email)->send(new ReceiptMail(
                $receipt->receipt_number,
                (string) $receipt->amount_paid,
                $receipt->payment_method,
                $pdf,
                "receipt-{$receipt->receipt_number}.pdf"
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
