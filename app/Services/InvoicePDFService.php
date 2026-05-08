<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePDFService
{
    public function __construct(
        private GSTCalculationService $gst
    ) {}

    public function generate(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['tenant', 'customer', 'items.product']);

        $gstGroups = $this->gst->groupByGSTRate(
            $invoice->items->map(fn ($item) => $item->toArray())->toArray()
        );

        $amountInWords = $this->gst->amountInWords($invoice->total_amount);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'gstGroups', 'amountInWords'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    public function download(Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $filename = 'Invoice-' . $invoice->invoice_number . '.pdf';
        return $this->generate($invoice)->download($filename);
    }

    public function stream(Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $filename = 'Invoice-' . $invoice->invoice_number . '.pdf';
        return $this->generate($invoice)->stream($filename);
    }

    public function base64(Invoice $invoice): string
    {
        return base64_encode($this->generate($invoice)->output());
    }
}
