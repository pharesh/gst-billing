<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $token;
    private string $phoneNumberId;

    public function __construct()
    {
        $this->apiUrl = 'https://graph.facebook.com/v18.0';
        $this->token = config('services.whatsapp.token', '');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->token) && ! empty($this->phoneNumberId);
    }

    // ─── Send Invoice PDF ────────────────────────────────────────────────

    public function sendInvoice(Invoice $invoice, InvoicePDFService $pdfService): bool
    {
        if (! $this->isConfigured() || ! $invoice->customer->phone) return false;

        $phone = $this->normalize($invoice->customer->phone);
        $pdfBase64 = $pdfService->base64($invoice);

        try {
            $uploadResponse = Http::withToken($this->token)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/media", [
                    'messaging_product' => 'whatsapp',
                    'type' => 'application/pdf',
                    'file' => $pdfBase64,
                ]);

            if (! $uploadResponse->successful()) return false;

            $mediaId = $uploadResponse->json('id');

            $response = Http::withToken($this->token)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'document',
                    'document' => [
                        'id' => $mediaId,
                        'filename' => "Invoice-{$invoice->invoice_number}.pdf",
                        'caption' => "Invoice #{$invoice->invoice_number}\nDate: {$invoice->invoice_date->format('d M Y')}\nAmount: ₹" . number_format($invoice->total_amount, 2) . "\n\nFrom: {$invoice->tenant->name}",
                    ],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WhatsApp invoice send failed: ' . $e->getMessage());
            return false;
        }
    }

    // ─── Payment Reminder ────────────────────────────────────────────────

    public function sendPaymentReminder(Invoice $invoice): bool
    {
        if (! $this->isConfigured() || ! $invoice->customer->phone) return false;

        $overdue = $invoice->isOverdue() ? ' ⚠️ OVERDUE' : '';
        $message = "Dear {$invoice->customer->name},\n\n"
            . "Reminder: Invoice #{$invoice->invoice_number} payment is pending.{$overdue}\n\n"
            . "Amount Due: ₹" . number_format($invoice->balance_due, 2) . "\n"
            . ($invoice->due_date ? "Due Date: " . $invoice->due_date->format('d M Y') . "\n" : '')
            . "\nPlease make the payment at the earliest.\n\n"
            . "Regards,\n{$invoice->tenant->name}";

        return $this->sendText($this->normalize($invoice->customer->phone), $message);
    }

    // ─── Payment Confirmed ───────────────────────────────────────────────

    public function sendPaymentConfirmation(Invoice $invoice, Payment $payment): bool
    {
        if (! $this->isConfigured() || ! $invoice->customer->phone) return false;

        $message = "Dear {$invoice->customer->name},\n\n"
            . "✅ Payment Received!\n\n"
            . "Invoice: #{$invoice->invoice_number}\n"
            . "Amount Paid: ₹" . number_format($payment->amount, 2) . "\n"
            . "Date: " . $payment->payment_date->format('d M Y') . "\n"
            . ($invoice->balance_due > 0
                ? "Balance Due: ₹" . number_format($invoice->balance_due, 2) . "\n"
                : "Status: FULLY PAID ✓\n")
            . "\nThank you!\n{$invoice->tenant->name}";

        return $this->sendText($this->normalize($invoice->customer->phone), $message);
    }

    // ─── Login OTP ───────────────────────────────────────────────────────

    public function sendOtp(string $phone, string $otp, string $name): bool
    {
        if (! $this->isConfigured()) return false;

        $message = "Hi {$name},\n\n"
            . "Your GST Billing login OTP is:\n\n"
            . "*{$otp}*\n\n"
            . "Valid for 10 minutes. Do not share this with anyone.";

        return $this->sendText($this->normalize($phone), $message);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function sendText(string $phone, string $message): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WhatsApp text failed: ' . $e->getMessage());
            return false;
        }
    }

    private function normalize(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        return $phone;
    }
}
