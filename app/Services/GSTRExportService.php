<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class GSTRExportService
{
    /**
     * Generate GSTR-1 data for a given month/year.
     * Returns structured data ready for JSON export or Excel.
     */
    public function gstr1(Tenant $tenant, int $month, int $year): array
    {
        // MongoDB stores dates as strings (Y-m-d); use range query instead of
        // whereMonth/whereYear which require BSON Date objects
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = sprintf('%04d-%02d-%02d', $year, $month, date('t', mktime(0, 0, 0, $month, 1, $year)));

        $invoices = Invoice::with(['customer', 'items'])
            ->where('tenant_id', $tenant->id)
            ->where('invoice_date', '>=', $startDate)
            ->where('invoice_date', '<=', $endDate)
            ->get();

        return [
            'gstin' => $tenant->gstin,
            'fp' => str_pad($month, 2, '0', STR_PAD_LEFT) . $year, // filing period e.g. 032024
            'b2b' => $this->buildB2B($invoices->where('invoice_type', 'b2b')),
            'b2cs' => $this->buildB2CS($invoices->where('invoice_type', 'b2c')),
            'summary' => $this->buildSummary($invoices),
        ];
    }

    /**
     * Generate GSTR-3B summary for a given month/year.
     */
    public function gstr3b(Tenant $tenant, int $month, int $year): array
    {
        // MongoDB stores dates as strings (Y-m-d); use range query instead of
        // whereMonth/whereYear which require BSON Date objects
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = sprintf('%04d-%02d-%02d', $year, $month, date('t', mktime(0, 0, 0, $month, 1, $year)));

        $invoices = Invoice::with('items')
            ->where('tenant_id', $tenant->id)
            ->where('invoice_date', '>=', $startDate)
            ->where('invoice_date', '<=', $endDate)
            ->get();

        $outward = [
            'taxable_value' => 0,
            'igst' => 0,
            'cgst' => 0,
            'sgst' => 0,
        ];

        foreach ($invoices as $invoice) {
            $outward['taxable_value'] += $invoice->subtotal;
            $outward['igst'] += $invoice->igst_amount;
            $outward['cgst'] += $invoice->cgst_amount;
            $outward['sgst'] += $invoice->sgst_amount;
        }

        return [
            'gstin' => $tenant->gstin,
            'period' => str_pad($month, 2, '0', STR_PAD_LEFT) . '/' . $year,
            'outward_supplies' => array_map(fn ($v) => round($v, 2), $outward),
            'total_tax_liability' => round($outward['igst'] + $outward['cgst'] + $outward['sgst'], 2),
            'invoice_count' => $invoices->count(),
        ];
    }

    /**
     * Export GSTR-1 as downloadable JSON (format accepted by GST portal).
     */
    public function exportGSTR1Json(Tenant $tenant, int $month, int $year): string
    {
        return json_encode($this->gstr1($tenant, $month, $year), JSON_PRETTY_PRINT);
    }

    private function buildB2B(Collection $invoices): array
    {
        return $invoices->groupBy('customer.gstin')->map(function ($group, $gstin) {
            return [
                'ctin' => $gstin,
                'inv' => $group->map(fn ($inv) => [
                    'inum' => $inv->invoice_number,
                    'idt' => $inv->invoice_date->format('d-m-Y'),
                    'val' => $inv->total_amount,
                    'pos' => $inv->customer->state_code,
                    'rchrg' => 'N',
                    'itms' => $inv->items->map(fn ($item) => [
                        'num' => 1,
                        'itm_det' => [
                            'rt' => $item->gst_rate,
                            'txval' => $item->taxable_amount,
                            'iamt' => $item->igst_amount,
                            'camt' => $item->cgst_amount,
                            'samt' => $item->sgst_amount,
                        ],
                    ])->values()->toArray(),
                ])->values()->toArray(),
            ];
        })->values()->toArray();
    }

    private function buildB2CS(Collection $invoices): array
    {
        // Group B2C by state and GST rate
        $groups = [];

        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $key = $invoice->customer->state_code . '_' . $item->gst_rate;
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'sply_ty' => $invoice->supply_type === 'interstate' ? 'INTER' : 'INTRA',
                        'pos' => $invoice->customer->state_code,
                        'rt' => $item->gst_rate,
                        'txval' => 0,
                        'iamt' => 0,
                        'camt' => 0,
                        'samt' => 0,
                    ];
                }
                $groups[$key]['txval'] += $item->taxable_amount;
                $groups[$key]['iamt'] += $item->igst_amount;
                $groups[$key]['camt'] += $item->cgst_amount;
                $groups[$key]['samt'] += $item->sgst_amount;
            }
        }

        return array_values($groups);
    }

    private function buildSummary(Collection $invoices): array
    {
        return [
            'total_invoices' => $invoices->count(),
            'total_taxable_value' => round($invoices->sum('subtotal'), 2),
            'total_igst' => round($invoices->sum('igst_amount'), 2),
            'total_cgst' => round($invoices->sum('cgst_amount'), 2),
            'total_sgst' => round($invoices->sum('sgst_amount'), 2),
            'total_invoice_value' => round($invoices->sum('total_amount'), 2),
        ];
    }
}
