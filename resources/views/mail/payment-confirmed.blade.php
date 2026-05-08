@extends('mail.layout')

@section('content')
<div class="header">
  <h1>Payment Received ✓</h1>
  <p>Invoice #{{ $invoice->invoice_number }} — {{ $invoice->tenant->name }}</p>
</div>
<div class="body">
  <p>Dear <strong>{{ $invoice->customer->name }}</strong>,</p>
  <p>We have received your payment. Thank you!</p>

  <div class="highlight-box">
    <div class="label">Amount Received</div>
    <div class="value" style="color:#16a34a;">₹{{ number_format($payment->amount, 2) }}</div>
  </div>

  <table class="info-table">
    <tr><td>Invoice No.</td><td>{{ $invoice->invoice_number }}</td></tr>
    <tr><td>Payment Date</td><td>{{ $payment->payment_date->format('d M Y') }}</td></tr>
    <tr><td>Payment Method</td><td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td></tr>
    @if($payment->reference_number)
    <tr><td>Reference / UTR</td><td>{{ $payment->reference_number }}</td></tr>
    @endif
    <tr><td>Invoice Total</td><td>₹{{ number_format($invoice->total_amount, 2) }}</td></tr>
    <tr><td>Total Paid</td><td class="status-paid">₹{{ number_format($invoice->amount_paid, 2) }}</td></tr>
    @if($invoice->balance_due > 0)
    <tr><td>Balance Due</td><td class="status-unpaid">₹{{ number_format($invoice->balance_due, 2) }}</td></tr>
    @else
    <tr><td>Status</td><td class="status-paid">FULLY PAID</td></tr>
    @endif
  </table>

  <p style="margin-top:20px;">Thank you for your prompt payment. We look forward to serving you again.</p>
  <p>Regards,<br><strong>{{ $invoice->tenant->name }}</strong></p>
</div>
@endsection
