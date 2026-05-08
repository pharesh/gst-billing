@extends('mail.layout')

@section('content')
<div class="header">
  <h1>Payment Reminder</h1>
  <p>Invoice #{{ $invoice->invoice_number }} — {{ $invoice->tenant->name }}</p>
</div>
<div class="body">
  <p>Dear <strong>{{ $invoice->customer->name }}</strong>,</p>
  <p>This is a friendly reminder that the following invoice is pending payment:</p>

  <div class="highlight-box">
    <div class="label">Amount Due</div>
    <div class="value">₹{{ number_format($invoice->balance_due, 2) }}</div>
  </div>

  <table class="info-table">
    <tr><td>Invoice No.</td><td>{{ $invoice->invoice_number }}</td></tr>
    <tr><td>Invoice Date</td><td>{{ $invoice->invoice_date->format('d M Y') }}</td></tr>
    @if($invoice->due_date)
    <tr>
      <td>Due Date</td>
      <td style="color:{{ $invoice->isOverdue() ? '#dc2626' : '#333' }}; font-weight:600;">
        {{ $invoice->due_date->format('d M Y') }}
        @if($invoice->isOverdue()) (OVERDUE) @endif
      </td>
    </tr>
    @endif
    <tr><td>Total Invoice</td><td>₹{{ number_format($invoice->total_amount, 2) }}</td></tr>
    @if($invoice->amount_paid > 0)
    <tr><td>Paid So Far</td><td class="status-paid">₹{{ number_format($invoice->amount_paid, 2) }}</td></tr>
    @endif
    <tr><td>Balance Due</td><td><strong>₹{{ number_format($invoice->balance_due, 2) }}</strong></td></tr>
  </table>

  @if($invoice->tenant->bank_details)
  @php $bank = $invoice->tenant->bank_details; @endphp
  <p style="margin-top:20px;"><strong>Please transfer to:</strong></p>
  <table class="info-table">
    @if($bank['bank_name'] ?? false)<tr><td>Bank</td><td>{{ $bank['bank_name'] }}</td></tr>@endif
    @if($bank['account_no'] ?? false)<tr><td>Account No.</td><td>{{ $bank['account_no'] }}</td></tr>@endif
    @if($bank['ifsc'] ?? false)<tr><td>IFSC Code</td><td>{{ $bank['ifsc'] }}</td></tr>@endif
  </table>
  @endif

  <p style="margin-top:20px;">Please make the payment at the earliest. If you have already paid, please ignore this reminder.</p>
  <p>Regards,<br><strong>{{ $invoice->tenant->name }}</strong><br>
  @if($invoice->tenant->phone)<small>{{ $invoice->tenant->phone }}</small>@endif</p>
</div>
@endsection
