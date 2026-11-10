<?php

namespace App\Observers;

use App\Mail\ReceiptMail;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\Accounting\JournalPostingService;
use App\Services\ReceiptPdfService;
use App\Services\SalePaymentService;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        if (strtolower((string) $payment->status) !== 'successful') {
            return;
        }

        $this->postJournal($payment);
        $this->emailReceipt($payment);
    }

    /** Edits from the Payments page (amount, status, sale) must flow back into the sale's balance. */
    public function updated(Payment $payment): void
    {
        if (! $payment->wasChanged(['amount', 'status', 'sale_id', 'method'])) {
            return;
        }

        try {
            app(JournalPostingService::class)->reverseForPayment($payment);
        } catch (Throwable $e) {
            report($e);
        }

        if (strtolower((string) $payment->status) === 'successful') {
            $this->postJournal($payment);
        }

        $this->syncSales($payment, $payment->getOriginal('sale_id'));
    }

    public function deleted(Payment $payment): void
    {
        try {
            app(JournalPostingService::class)->reverseForPayment($payment);
        } catch (Throwable $e) {
            report($e);
        }

        $this->syncSales($payment);
    }

    protected function postJournal(Payment $payment): void
    {
        try {
            app(JournalPostingService::class)->postForPayment($payment);
        } catch (Throwable $e) {
            // Never block a payment because of a bookkeeping error.
            report($e);
        }
    }

    protected function syncSales(Payment $payment, mixed $previousSaleId = null): void
    {
        $ids = array_unique(array_filter([$payment->sale_id, $previousSaleId]));

        foreach (Sale::whereIn('id', $ids)->get() as $sale) {
            app(SalePaymentService::class)->sync($sale);
        }
    }

    protected function emailReceipt(Payment $payment): void
    {

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
