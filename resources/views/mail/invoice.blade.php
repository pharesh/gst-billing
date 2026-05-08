@extends('mail.layout')

@section('content')
<div class="header">
  <h1>Invoice from {{ $invoice->tenant->name }}</h1>
  <p>Invoice #{{ $invoice->invoice_number }} • {{ $invoice->invoice_date->format('d M Y') }}</p>
</div>
<div class="body">
  <p>Dear <strong>{{ $invoice->customer->name }}</strong>,</p>
  <p>Please find your invoice attached to this email. Here is a summary:</p>

  <div class="highlight-box">
    <div class="label">Amount Due</div>
    <div class="value">₹{{ number_format($invoice->balance_due, 2) }}</div>
  </div>

  <table class="info-table">
    <tr><td>Invoice No.</td><td>{{ $invoice->invoice_number }}</td></tr>
    <tr><td>Invoice Date</td><td>{{ $invoice->invoice_date->format('d M Y') }}</td></tr>
    @if($invoice->due_date)
    <tr><td>Due Date</td><td>{{ $invoice->due_date->format('d M Y') }}</td></tr>
    @endif
    <tr><td>Subtotal</td><td>₹{{ number_format($invoice->subtotal, 2) }}</td></tr>
    <tr><td>GST</td><td>₹{{ number_format($invoice->cgst_amount + $invoice->sgst_amount + $invoice->igst_amount, 2) }}</td></tr>
    <tr><td>Total Amount</td><td><strong>₹{{ number_format($invoice->total_amount, 2) }}</strong></td></tr>
    <tr><td>Payment Status</td><td class="status-{{ $invoice->payment_status }}">{{ strtoupper($invoice->payment_status) }}</td></tr>
  </table>

  @if($invoice->tenant->bank_details)
  @php $bank = $invoice->tenant->bank_details; @endphp
  <p style="margin-top:20px;"><strong>Payment Details:</strong></p>
  <table class="info-table">
    @if($bank['bank_name'] ?? false)<tr><td>Bank</td><td>{{ $bank['bank_name'] }}</td></tr>@endif
    @if($bank['account_no'] ?? false)<tr><td>Account No.</td><td>{{ $bank['account_no'] }}</td></tr>@endif
    @if($bank['ifsc'] ?? false)<tr><td>IFSC Code</td><td>{{ $bank['ifsc'] }}</td></tr>@endif
  </table>
  @endif

  @if($invoice->notes)
  <p style="color:#666;font-size:13px;margin-top:16px;"><em>Note: {{ $invoice->notes }}</em></p>
  @endif

  <p style="margin-top:20px;">Thank you for your business!</p>
  <p>Regards,<br><strong>{{ $invoice->tenant->name }}</strong></p>
</div>
@endsection
