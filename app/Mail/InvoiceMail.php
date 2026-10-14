<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

/** Sent automatically whenever a sale/invoice is created, with the invoice PDF attached. */
class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sale $sale, public string $pdfContent, public string $fileName)
    {
    }

    public function build()
    {
        return $this->subject("Invoice {$this->sale->invoice_no}")
            ->markdown('emails.invoice')
            ->attachData($this->pdfContent, $this->fileName, ['mime' => 'application/pdf']);
    }
}
