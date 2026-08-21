<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Http\Response;

class InvoicePdfService
{
    public function generatePdf(Invoice $invoice): DomPdfWrapper
    {
        $invoice->load(['tenant', 'owner', 'unit.property']);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ])->setPaper('a4', 'portrait');
    }

    public function downloadResponse(Invoice $invoice): Response
    {
        $pdf = $this->generatePdf($invoice);
        $filename = 'Rechnung_'.str_replace(['/', '\\', ' '], '_', $invoice->invoice_number).'.pdf';

        return $pdf->download($filename);
    }

    public function streamResponse(Invoice $invoice): Response
    {
        $pdf = $this->generatePdf($invoice);
        $filename = 'Rechnung_'.str_replace(['/', '\\', ' '], '_', $invoice->invoice_number).'.pdf';

        return $pdf->stream($filename);
    }
}
