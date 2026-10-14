<?php

namespace App\Observers;

use App\Mail\InvoiceMail;
use App\Models\Sale;
use App\Support\DocumentBranding;
use App\Services\Accounting\JournalPostingService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SaleObserver
{
    public function created(Sale $sale): void
    {
        try {
            app(JournalPostingService::class)->postForSale($sale);
        } catch (Throwable $e) {
            // Never block a sale from saving because of a bookkeeping error —
            // log it so it can be posted/fixed manually instead.
            report($e);
        }

        $this->emailInvoice($sale);
    }

    /** Auto-emails the invoice PDF to the customer, if we have an email for them. */
    protected function emailInvoice(Sale $sale): void
    {
        try {
            $sale->loadMissing(['customer', 'sale_items.product', 'branch']);
            $email = $sale->customer?->email;
            if (! $email) {
                return;
            }

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml(view('sales.invoice-pdf', ['sale' => $sale, 'branding' => DocumentBranding::get()])->render());
            $dompdf->setPaper('A4');
            $dompdf->render();

            Mail::to($email)->send(new InvoiceMail($sale, $dompdf->output(), "invoice-{$sale->invoice_no}.pdf"));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
