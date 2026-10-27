<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Sent on demand from the delivery note preview, with the delivery note PDF attached. */
class DeliveryNoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sale $sale, public string $pdfContent, public string $fileName, public ?string $note = null)
    {
    }

    public function build()
    {
        return $this->subject("Delivery Note {$this->sale->delivery_note_no}")
            ->markdown('emails.delivery-note')
            ->attachData($this->pdfContent, $this->fileName, ['mime' => 'application/pdf']);
    }
}
