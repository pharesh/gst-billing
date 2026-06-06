<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EInvoiceService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $username;
    private string $password;

    public function __construct()
    {
        $sandbox       = config('services.einvoice.sandbox', true);
        $this->baseUrl = $sandbox
            ? 'https://einv-apisandbox.nic.in'
            : 'https://api.invoice-registration.nic.in';

        $this->clientId     = config('services.einvoice.client_id', '');
        $this->clientSecret = config('services.einvoice.client_secret', '');
        $this->username     = config('services.einvoice.username', '');
        $this->password     = config('services.einvoice.password', '');
    }

    public function generateIRN(Invoice $invoice): array
    {
        $invoice->load(['tenant', 'customer', 'items']);

        $token    = $this->getAuthToken($invoice->tenant->gstin);
        $payload  = $this->buildPayload($invoice);

        $response = Http::withHeaders([
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'user_name'     => $this->username,
            'Gstin'         => $invoice->tenant->gstin,
            'AuthToken'     => $token,
        ])->put("{$this->baseUrl}/eicore/v1.03/Invoice", $payload);

        if (!$response->successful()) {
            $body = $response->json();
            $msg  = data_get($body, 'message', 'NIC API error: ' . $response->status());
            throw new \RuntimeException($msg);
        }

        $data = $response->json('data', []);

        return [
            'irn'             => $data['Irn'] ?? '',
            'ack_no'          => $data['AckNo'] ?? '',
            'ack_date'        => $data['AckDt'] ?? now()->toDateString(),
            'signed_qr_code'  => $data['SignedQRCode'] ?? '',
        ];
    }

    public function cancelIRN(Invoice $invoice, string $cancelReason = '1'): void
    {
        $invoice->load('tenant');

        $token = $this->getAuthToken($invoice->tenant->gstin);

        $response = Http::withHeaders([
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'user_name'     => $this->username,
            'Gstin'         => $invoice->tenant->gstin,
            'AuthToken'     => $token,
        ])->post("{$this->baseUrl}/eicore/v1.03/Invoice/Cancel", [
            'Irn'          => $invoice->irn,
            'CnlRsn'       => $cancelReason, // 1=Duplicate, 2=Data Entry Mistake, 3=Order Cancelled, 4=Others
            'CnlRem'       => 'Cancelled by taxpayer',
        ]);

        if (!$response->successful()) {
            $body = $response->json();
            $msg  = data_get($body, 'message', 'Cancel IRN failed: ' . $response->status());
            throw new \RuntimeException($msg);
        }
    }

    private function getAuthToken(string $gstin): string
    {
        $cacheKey = "einvoice_token_{$gstin}";

        return Cache::remember($cacheKey, now()->addMinutes(55), function () use ($gstin) {
            $response = Http::withHeaders([
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'user_name'     => $this->username,
                'Gstin'         => $gstin,
            ])->get("{$this->baseUrl}/eivital/v1.04/auth", [
                'user_name' => $this->username,
                'password'  => $this->password,
                'gstin'     => $gstin,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('E-Invoice auth failed: ' . $response->body());
            }

            return $response->json('data.AuthToken');
        });
    }

    private function buildPayload(Invoice $invoice): array
    {
        $tenant   = $invoice->tenant;
        $customer = $invoice->customer;

        $txnType = match ($invoice->invoice_type) {
            'b2b'    => 'B2B',
            'b2c'    => 'B2C',
            'export' => 'EXPWOP',
            default  => 'B2B',
        };

        $supplyType = $invoice->supply_type === 'interstate' ? 'INTER' : 'INTRA';

        $itemList = $invoice->items->map(function ($item, $i) use ($invoice) {
            $row = [
                'SlNo'    => (string)($i + 1),
                'PrdDesc' => $item->description,
                'IsServc' => 'Y',
                'HsnCd'   => $item->hsn_sac_code ?? '9997',
                'Qty'     => round($item->quantity, 3),
                'Unit'    => strtoupper($item->unit ?? 'NOS'),
                'UnitPrice' => round($item->price, 2),
                'TotAmt'    => round($item->taxable_amount, 2),
                'Discount'  => round($item->discount_amount ?? 0, 2),
                'PreTaxVal' => round($item->taxable_amount, 2),
                'AssAmt'    => round($item->taxable_amount, 2),
                'GstRt'     => round($item->gst_rate, 2),
                'TotItemVal' => round($item->total_amount, 2),
            ];

            if ($invoice->supply_type === 'intrastate') {
                $row['CgstAmt'] = round($item->cgst_amount, 2);
                $row['SgstAmt'] = round($item->sgst_amount, 2);
                $row['IgstAmt'] = 0.00;
            } else {
                $row['CgstAmt'] = 0.00;
                $row['SgstAmt'] = 0.00;
                $row['IgstAmt'] = round($item->igst_amount, 2);
            }

            return $row;
        })->values()->toArray();

        $valDtls = [
            'AssVal'  => round($invoice->subtotal, 2),
            'CgstVal' => round($invoice->cgst_amount, 2),
            'SgstVal' => round($invoice->sgst_amount, 2),
            'IgstVal' => round($invoice->igst_amount, 2),
            'Discount' => round($invoice->discount_amount ?? 0, 2),
            'TotInvVal' => round($invoice->total_amount, 2),
        ];

        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch'   => 'GST',
                'SupTyp'   => $txnType,
                'RegRev'   => 'N',
                'EcmGstin' => null,
            ],
            'DocDtls' => [
                'Typ'  => 'INV',
                'No'   => $invoice->invoice_number,
                'Dt'   => $invoice->invoice_date->format('d/m/Y'),
            ],
            'SellerDtls' => [
                'Gstin'    => $tenant->gstin,
                'LglNm'    => $tenant->name,
                'TrdNm'    => $tenant->name,
                'Addr1'    => $tenant->address ?? 'N/A',
                'Loc'      => $tenant->city ?? 'N/A',
                'Pin'      => (int)($tenant->pincode ?? 0),
                'Stcd'     => str_pad((string)($tenant->state_code ?? 0), 2, '0', STR_PAD_LEFT),
                'Ph'       => $tenant->phone ?? '',
                'Em'       => $tenant->email ?? '',
            ],
            'BuyerDtls' => [
                'Gstin'    => $customer->gstin ?? 'URP',
                'LglNm'    => $customer->name,
                'TrdNm'    => $customer->name,
                'Pos'      => str_pad((string)($customer->state_code ?? $tenant->state_code ?? 0), 2, '0', STR_PAD_LEFT),
                'Addr1'    => $customer->address ?? 'N/A',
                'Loc'      => $customer->city ?? 'N/A',
                'Pin'      => (int)($customer->pincode ?? 0),
                'Stcd'     => str_pad((string)($customer->state_code ?? 0), 2, '0', STR_PAD_LEFT),
                'Ph'       => $customer->phone ?? '',
                'Em'       => $customer->email ?? '',
            ],
            'ItemList' => $itemList,
            'ValDtls'  => $valDtls,
        ];
    }
}
