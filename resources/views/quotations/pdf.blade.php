<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; }
        .page { padding: 20px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 10px; }
        .header-left { display: table-cell; width: 60%; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; text-align: right; vertical-align: top; }
        .company-name { font-size: 18px; font-weight: bold; color: #1a1a2e; }
        .company-gstin { font-size: 10px; color: #555; margin-top: 2px; }
        .doc-title { font-size: 16px; font-weight: bold; color: #1a1a2e; }
        .doc-meta { font-size: 10px; margin-top: 4px; color: #444; }
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
        .validity-notice { background: #fff8e1; border: 1px solid #ffe082; padding: 6px 10px; border-radius: 3px; font-size: 10px; margin-bottom: 10px; }
        .footer { border-top: 1px solid #ddd; margin-top: 10px; padding-top: 10px; display: table; width: 100%; }
        .footer-left { display: table-cell; width: 60%; font-size: 9px; color: #666; }
        .footer-right { display: table-cell; width: 40%; text-align: right; font-size: 10px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            @if($quotation->tenant->logo)
                <img src="{{ public_path('storage/' . $quotation->tenant->logo) }}" height="50" style="margin-bottom:6px;"><br>
            @endif
            <div class="company-name">{{ $quotation->tenant->name }}</div>
            @if($quotation->tenant->address)
                <div class="company-gstin">{{ $quotation->tenant->address }}, {{ $quotation->tenant->city }}, {{ $quotation->tenant->state }} - {{ $quotation->tenant->pincode }}</div>
            @endif
            @if($quotation->tenant->gstin)
                <div class="company-gstin">GSTIN: {{ $quotation->tenant->gstin }}</div>
            @endif
            @if($quotation->tenant->phone)
                <div class="company-gstin">Phone: {{ $quotation->tenant->phone }} | Email: {{ $quotation->tenant->email }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="doc-title">PROFORMA INVOICE / QUOTATION</div>
            <div class="doc-meta">
                <strong>Quote #:</strong> {{ $quotation->quotation_number }}<br>
                <strong>Date:</strong> {{ $quotation->quotation_date->format('d M Y') }}<br>
                @if($quotation->valid_until)
                    <strong>Valid Until:</strong> {{ $quotation->valid_until->format('d M Y') }}<br>
                @endif
                <strong>Status:</strong> <span class="badge">{{ strtoupper($quotation->status) }}</span>
            </div>
        </div>
    </div>

    {{-- Bill To --}}
    <div class="parties">
        <div class="party-box">
            <div class="party-label">Quoted To</div>
            <div class="party-name">{{ $quotation->customer->name }}</div>
            @if($quotation->customer->gstin)
                <div class="party-detail">GSTIN: {{ $quotation->customer->gstin }}</div>
            @endif
            @if($quotation->customer->address)
                <div class="party-detail">{{ $quotation->customer->address }}, {{ $quotation->customer->city }}</div>
                <div class="party-detail">{{ $quotation->customer->state }} - {{ $quotation->customer->pincode }}</div>
            @endif
            @if($quotation->customer->phone)
                <div class="party-detail">Phone: {{ $quotation->customer->phone }}</div>
            @endif
        </div>
        <div class="party-box">
            <div class="party-label">Quote Details</div>
            <div class="party-detail"><strong>Supply Type:</strong> {{ ucfirst($quotation->supply_type) }}</div>
            <div class="party-detail"><strong>Quote Type:</strong> {{ strtoupper($quotation->invoice_type) }}</div>
            @if($quotation->tenant->bank_details)
                <div class="party-label" style="margin-top:8px;">Bank Details</div>
                @php $bank = $quotation->tenant->bank_details; @endphp
                <div class="party-detail">{{ $bank['bank_name'] ?? '' }}</div>
                <div class="party-detail">A/C: {{ $bank['account_no'] ?? '' }}</div>
                <div class="party-detail">IFSC: {{ $bank['ifsc'] ?? '' }}</div>
            @endif
        </div>
    </div>

    @if($quotation->valid_until)
    <div class="validity-notice">
        This quotation is valid until <strong>{{ $quotation->valid_until->format('d M Y') }}</strong>.
        Prices are subject to change after this date.
    </div>
    @endif

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
                @if($quotation->supply_type === 'intrastate')
                    <th class="text-right">CGST</th>
                    <th class="text-right">SGST</th>
                @else
                    <th class="text-right">IGST</th>
                @endif
                <th class="text-right">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->hsn_sac_code ?? '-' }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-center">{{ $item->unit }}</td>
                <td class="text-right">{{ number_format($item->price, 2) }}</td>
                <td class="text-right">{{ number_format($item->taxable_amount, 2) }}</td>
                @if($quotation->supply_type === 'intrastate')
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
            <td class="text-right">₹ {{ number_format($quotation->subtotal, 2) }}</td>
        </tr>
        @if($quotation->cgst_amount > 0)
        <tr>
            <td class="label">CGST</td>
            <td class="text-right">₹ {{ number_format($quotation->cgst_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">SGST</td>
            <td class="text-right">₹ {{ number_format($quotation->sgst_amount, 2) }}</td>
        </tr>
        @endif
        @if($quotation->igst_amount > 0)
        <tr>
            <td class="label">IGST</td>
            <td class="text-right">₹ {{ number_format($quotation->igst_amount, 2) }}</td>
        </tr>
        @endif
        @if($quotation->discount_amount > 0)
        <tr>
            <td class="label">Discount</td>
            <td class="text-right">- ₹ {{ number_format($quotation->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td>Total Amount</td>
            <td class="text-right">₹ {{ number_format($quotation->total_amount, 2) }}</td>
        </tr>
    </table>

    {{-- Amount in Words --}}
    <div class="amount-words" style="clear:both;">
        <strong>Amount in Words:</strong> {{ $amountInWords }}
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">
            @if($quotation->notes)
                <strong>Notes:</strong> {{ $quotation->notes }}<br>
            @endif
            @if($quotation->terms)
                <strong>Terms & Conditions:</strong> {{ $quotation->terms }}<br>
            @endif
            <br>This is a computer-generated quotation. Not a tax invoice.
        </div>
        <div class="footer-right">
            <br><br>
            @if($quotation->tenant->signature)
                <img src="{{ public_path('storage/' . $quotation->tenant->signature) }}" height="40"><br>
            @endif
            <strong>{{ $quotation->tenant->name }}</strong><br>
            Authorised Signatory
        </div>
    </div>

</div>
</body>
</html>
