<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; }
        .page { padding: 20px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 10px; }
        .header-left { display: table-cell; width: 60%; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; text-align: right; vertical-align: top; }
        .company-name { font-size: 18px; font-weight: bold; color: #1a1a2e; }
        .company-gstin { font-size: 10px; color: #555; margin-top: 2px; }
        .invoice-title { font-size: 16px; font-weight: bold; color: #1a1a2e; }
        .invoice-meta { font-size: 10px; margin-top: 4px; color: #444; }
        .parties { display: table; width: 100%; margin-bottom: 12px; }
        .party-box { display: table-cell; width: 50%; vertical-align: top; padding: 8px; border: 1px solid #ddd; }
        .party-box:first-child { border-right: none; }
        .party-label { font-size: 9px; font-weight: bold; color: #888; text-transform: uppercase; margin-bottom: 4px; }
        .party-name { font-size: 12px; font-weight: bold; }
        .party-detail { font-size: 10px; color: #444; margin-top: 2px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.items th { background: #1a1a2e; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
        table.items td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        table.items tr:nth-child(even) td { background: #f9f9f9; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-table { width: 280px; float: right; border-collapse: collapse; margin-bottom: 10px; }
        .totals-table td { padding: 4px 8px; font-size: 10px; }
        .totals-table .label { color: #555; }
        .totals-table .grand-total { font-weight: bold; font-size: 12px; background: #1a1a2e; color: #fff; }
        .amount-words { clear: both; background: #f5f5f5; padding: 8px; border-radius: 3px; font-size: 10px; margin-bottom: 10px; }
        .tax-summary { margin-bottom: 10px; }
        .tax-summary table { width: 100%; border-collapse: collapse; }
        .tax-summary table th { background: #f0f0f0; padding: 5px 8px; font-size: 9px; border: 1px solid #ddd; }
        .tax-summary table td { padding: 4px 8px; font-size: 10px; border: 1px solid #ddd; }
        .footer { border-top: 1px solid #ddd; margin-top: 10px; padding-top: 10px; display: table; width: 100%; }
        .footer-left { display: table-cell; width: 60%; font-size: 9px; color: #666; }
        .footer-right { display: table-cell; width: 40%; text-align: right; font-size: 10px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-unpaid { background: #f8d7da; color: #721c24; }
        .badge-partial { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            @if($invoice->tenant->logo)
                <img src="{{ public_path('storage/' . $invoice->tenant->logo) }}" height="50" style="margin-bottom:6px;"><br>
            @endif
            <div class="company-name">{{ $invoice->tenant->name }}</div>
            @if($invoice->tenant->address)
                <div class="company-gstin">{{ $invoice->tenant->address }}, {{ $invoice->tenant->city }}, {{ $invoice->tenant->state }} - {{ $invoice->tenant->pincode }}</div>
            @endif
            @if($invoice->tenant->gstin)
                <div class="company-gstin">GSTIN: {{ $invoice->tenant->gstin }}</div>
            @endif
            @if($invoice->tenant->phone)
                <div class="company-gstin">Phone: {{ $invoice->tenant->phone }} | Email: {{ $invoice->tenant->email }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="invoice-title">TAX INVOICE</div>
            <div class="invoice-meta">
                <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Date:</strong> {{ $invoice->invoice_date->format('d M Y') }}<br>
                @if($invoice->due_date)
                    <strong>Due Date:</strong> {{ $invoice->due_date->format('d M Y') }}<br>
                @endif
                <strong>Status:</strong>
                <span class="badge badge-{{ $invoice->payment_status }}">{{ strtoupper($invoice->payment_status) }}</span>
            </div>
        </div>
    </div>

    {{-- Bill To / From --}}
    <div class="parties">
        <div class="party-box">
            <div class="party-label">Bill To</div>
            <div class="party-name">{{ $invoice->customer->name }}</div>
            @if($invoice->customer->gstin)
                <div class="party-detail">GSTIN: {{ $invoice->customer->gstin }}</div>
            @endif
            @if($invoice->customer->address)
                <div class="party-detail">{{ $invoice->customer->address }}, {{ $invoice->customer->city }}</div>
                <div class="party-detail">{{ $invoice->customer->state }} - {{ $invoice->customer->pincode }}</div>
            @endif
            @if($invoice->customer->phone)
                <div class="party-detail">Phone: {{ $invoice->customer->phone }}</div>
            @endif
        </div>
        <div class="party-box">
            <div class="party-label">Supply Details</div>
            <div class="party-detail"><strong>Supply Type:</strong> {{ ucfirst($invoice->supply_type) }}</div>
            <div class="party-detail"><strong>Invoice Type:</strong> {{ strtoupper($invoice->invoice_type) }}</div>
            @if($invoice->tenant->bank_details)
                <div class="party-label" style="margin-top:8px;">Bank Details</div>
                @php $bank = $invoice->tenant->bank_details; @endphp
                <div class="party-detail">{{ $bank['bank_name'] ?? '' }}</div>
                <div class="party-detail">A/C: {{ $bank['account_no'] ?? '' }}</div>
                <div class="party-detail">IFSC: {{ $bank['ifsc'] ?? '' }}</div>
            @endif
        </div>
    </div>

    {{-- Items Table --}}
    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th>HSN/SAC</th>
                <th>Qty</th>
                <th>Unit</th>
                <th class="text-right">Rate (₹)</th>
                <th class="text-right">Taxable Value (₹)</th>
                @if($invoice->supply_type === 'intrastate')
                    <th class="text-right">CGST</th>
                    <th class="text-right">SGST</th>
                @else
                    <th class="text-right">IGST</th>
                @endif
                <th class="text-right">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->hsn_sac_code ?? '-' }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-center">{{ $item->unit }}</td>
                <td class="text-right">{{ number_format($item->price, 2) }}</td>
                <td class="text-right">{{ number_format($item->taxable_amount, 2) }}</td>
                @if($invoice->supply_type === 'intrastate')
                    <td class="text-right">{{ $item->cgst_rate }}%<br>{{ number_format($item->cgst_amount, 2) }}</td>
                    <td class="text-right">{{ $item->sgst_rate }}%<br>{{ number_format($item->sgst_amount, 2) }}</td>
                @else
                    <td class="text-right">{{ $item->igst_rate }}%<br>{{ number_format($item->igst_amount, 2) }}</td>
                @endif
                <td class="text-right"><strong>{{ number_format($item->total_amount, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-table">
        <tr>
            <td class="label">Subtotal (Taxable Value)</td>
            <td class="text-right">₹ {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->cgst_amount > 0)
        <tr>
            <td class="label">CGST</td>
            <td class="text-right">₹ {{ number_format($invoice->cgst_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">SGST</td>
            <td class="text-right">₹ {{ number_format($invoice->sgst_amount, 2) }}</td>
        </tr>
        @endif
        @if($invoice->igst_amount > 0)
        <tr>
            <td class="label">IGST</td>
            <td class="text-right">₹ {{ number_format($invoice->igst_amount, 2) }}</td>
        </tr>
        @endif
        @if($invoice->discount_amount > 0)
        <tr>
            <td class="label">Discount</td>
            <td class="text-right">- ₹ {{ number_format($invoice->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td>Total Amount</td>
            <td class="text-right">₹ {{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
        @if($invoice->amount_paid > 0)
        <tr>
            <td class="label">Amount Paid</td>
            <td class="text-right">₹ {{ number_format($invoice->amount_paid, 2) }}</td>
        </tr>
        <tr>
            <td class="label"><strong>Balance Due</strong></td>
            <td class="text-right"><strong>₹ {{ number_format($invoice->balance_due, 2) }}</strong></td>
        </tr>
        @endif
    </table>

    {{-- Amount in Words --}}
    <div class="amount-words" style="clear:both;">
        <strong>Amount in Words:</strong> {{ $amountInWords }}
    </div>

    {{-- GST Tax Summary --}}
    @if(count($gstGroups) > 0)
    <div class="tax-summary">
        <table>
            <thead>
                <tr>
                    <th>GST Rate</th>
                    <th class="text-right">Taxable Amount (₹)</th>
                    @if($invoice->supply_type === 'intrastate')
                        <th class="text-right">CGST (₹)</th>
                        <th class="text-right">SGST (₹)</th>
                    @else
                        <th class="text-right">IGST (₹)</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($gstGroups as $group)
                <tr>
                    <td>{{ $group['gst_rate'] }}%</td>
                    <td class="text-right">{{ number_format($group['taxable_amount'], 2) }}</td>
                    @if($invoice->supply_type === 'intrastate')
                        <td class="text-right">{{ number_format($group['cgst_amount'], 2) }}</td>
                        <td class="text-right">{{ number_format($group['sgst_amount'], 2) }}</td>
                    @else
                        <td class="text-right">{{ number_format($group['igst_amount'], 2) }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Notes & Footer --}}
    <div class="footer">
        <div class="footer-left">
            @if($invoice->notes)
                <strong>Notes:</strong> {{ $invoice->notes }}<br>
            @endif
            @if($invoice->terms)
                <strong>Terms & Conditions:</strong> {{ $invoice->terms }}<br>
            @endif
            <br>This is a computer-generated invoice.
        </div>
        <div class="footer-right">
            <br><br>
            @if($invoice->tenant->signature)
                <img src="{{ public_path('storage/' . $invoice->tenant->signature) }}" height="40"><br>
            @endif
            <strong>{{ $invoice->tenant->name }}</strong><br>
            Authorised Signatory
        </div>
    </div>

</div>
</body>
</html>
