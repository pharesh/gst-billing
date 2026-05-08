<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePDFService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        private InvoicePDFService $pdfService
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice #{$this->invoice->invoice_number} from {$this->invoice->tenant->name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invoice');
    }

    public function attachments(): array
    {
        $pdf = $this->pdfService->generate($this->invoice)->output();
        $filename = 'Invoice-' . $this->invoice->invoice_number . '.pdf';

        return [
            Attachment::fromData(fn () => $pdf, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
