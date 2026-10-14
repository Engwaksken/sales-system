<?php

namespace App\Services;

use App\Support\DocumentBranding;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReceiptPdfService
{
    /**
     * Renders the shared branded receipt template and returns raw PDF bytes.
     */
    public static function render(
        string $receiptNo,
        string $date,
        string $customerName,
        string $method,
        float $amount,
        ?string $reference = null
    ): string {
        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml(
            view('receipts.receipt-pdf', [
                'receiptNo' => $receiptNo,
                'date' => $date,
                'customerName' => $customerName,
                'method' => $method,
                'amount' => $amount,
                'reference' => $reference,
                'branding' => DocumentBranding::get(),
            ])->render()
        );

        $dompdf->setPaper('A5');
        $dompdf->render();

        return $dompdf->output();
    }
}
