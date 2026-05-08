<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Mail\LoginOtpMail;
use App\Mail\PaymentConfirmedMail;
use App\Mail\PaymentReminderMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(
        private WhatsAppService $whatsApp,
        private InvoicePDFService $pdfService
    ) {}

    // ─── Invoice Sent ────────────────────────────────────────────────────

    public function sendInvoice(Invoice $invoice): array
    {
        $results = ['email' => false, 'whatsapp' => false];

        if ($email = $invoice->customer->email) {
            try {
                Mail::to($email)->send(new InvoiceMail($invoice, $this->pdfService));
                $results['email'] = true;
            } catch (\Throwable $e) {
                Log::error('Invoice email failed: ' . $e->getMessage());
            }
        }

        if ($invoice->customer->phone) {
            $results['whatsapp'] = $this->whatsApp->sendInvoice($invoice, $this->pdfService);
        }

        return $results;
    }

    // ─── Payment Reminder ────────────────────────────────────────────────

    public function sendPaymentReminder(Invoice $invoice): array
    {
        $results = ['email' => false, 'whatsapp' => false];

        if ($email = $invoice->customer->email) {
            try {
                Mail::to($email)->send(new PaymentReminderMail($invoice));
                $results['email'] = true;
            } catch (\Throwable $e) {
                Log::error('Reminder email failed: ' . $e->getMessage());
            }
        }

        if ($invoice->customer->phone) {
            $results['whatsapp'] = $this->whatsApp->sendPaymentReminder($invoice);
        }

        return $results;
    }

    // ─── Payment Confirmed ───────────────────────────────────────────────

    public function sendPaymentConfirmed(Invoice $invoice, Payment $payment): array
    {
        $results = ['email' => false, 'whatsapp' => false];

        if ($email = $invoice->customer->email) {
            try {
                Mail::to($email)->send(new PaymentConfirmedMail($invoice, $payment));
                $results['email'] = true;
            } catch (\Throwable $e) {
                Log::error('Payment confirmed email failed: ' . $e->getMessage());
            }
        }

        if ($invoice->customer->phone) {
            $results['whatsapp'] = $this->whatsApp->sendPaymentConfirmation($invoice, $payment);
        }

        return $results;
    }

    // ─── Login OTP ───────────────────────────────────────────────────────

    public function sendLoginOtp(User $user): array
    {
        $otp = $user->generateOtp();
        $results = ['email' => false, 'whatsapp' => false];

        // Send via email
        try {
            Mail::to($user->email)->send(new LoginOtpMail($otp, $user->name));
            $results['email'] = true;
        } catch (\Throwable $e) {
            Log::error('OTP email failed: ' . $e->getMessage());
        }

        // Send via WhatsApp if user has a linked phone
        if ($user->tenant?->phone) {
            $results['whatsapp'] = $this->whatsApp->sendOtp($user->tenant->phone, $otp, $user->name);
        }

        return $results;
    }
}
