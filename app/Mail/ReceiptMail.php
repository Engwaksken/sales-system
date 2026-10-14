<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Sent automatically whenever a payment receipt is generated (sale payment or order payment), PDF attached. */
class ReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $receiptNo,
        public string $amount,
        public string $method,
        public string $pdfContent,
        public string $fileName
    ) {
    }

    public function build()
    {
        return $this->subject("Payment Receipt {$this->receiptNo}")
            ->markdown('emails.receipt')
            ->attachData($this->pdfContent, $this->fileName, ['mime' => 'application/pdf']);
    }
}
