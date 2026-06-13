<?php

namespace App\Http\Controllers;

use App\Services\GSTRExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GSTReportController extends Controller
{
    public function __construct(private GSTRExportService $exporter) {}

    public function aging(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $unpaid = \App\Models\Invoice::with('customer:id,name')
            ->where('tenant_id', $tenant->id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->get(['id', 'invoice_number', 'customer_id', 'due_date', 'total_amount', 'amount_paid']);

        $buckets = ['current' => [], '1_30' => [], '31_60' => [], '61_90' => [], 'over_90' => []];

        foreach ($unpaid as $inv) {
            $daysOverdue = (int) max(0, now()->diffInDays($inv->due_date, false) * -1);
            $balance     = round($inv->total_amount - $inv->amount_paid, 2);

            $row = [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'customer_name'  => $inv->customer?->name,
                'due_date'       => $inv->due_date?->format('Y-m-d'),
                'days_overdue'   => $daysOverdue,
                'balance_due'    => $balance,
            ];

            if ($daysOverdue === 0)     $buckets['current'][] = $row;
            elseif ($daysOverdue <= 30) $buckets['1_30'][]    = $row;
            elseif ($daysOverdue <= 60) $buckets['31_60'][]   = $row;
            elseif ($daysOverdue <= 90) $buckets['61_90'][]   = $row;
            else                        $buckets['over_90'][] = $row;
        }

        $summary = collect($buckets)->map(fn ($rows) => [
            'count' => count($rows),
            'total' => round(collect($rows)->sum('balance_due'), 2),
        ]);

        return response()->json([
            'buckets'           => $buckets,
            'summary'           => $summary,
            'total_outstanding' => round($unpaid->sum(fn ($i) => $i->total_amount - $i->amount_paid), 2),
        ]);
    }

    public function gstr1(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2017|max:2099',
        ]);

        $data = $this->exporter->gstr1(
            $request->user()->tenant,
            (int) $request->month,
            (int) $request->year
        );

        return response()->json(['data' => $data, 'month' => $request->month, 'year' => $request->year]);
    }

    public function gstr3b(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2017|max:2099',
        ]);

        $data = $this->exporter->gstr3b(
            $request->user()->tenant,
            (int) $request->month,
            (int) $request->year
        );

        return response()->json(['data' => $data, 'month' => $request->month, 'year' => $request->year]);
    }

    public function downloadGSTR1(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2017|max:2099',
        ]);

        $json = $this->exporter->exportGSTR1Json(
            $request->user()->tenant,
            (int) $request->month,
            (int) $request->year
        );

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="GSTR1_'.$request->month.'_'.$request->year.'.json"',
        ]);
    }
}
