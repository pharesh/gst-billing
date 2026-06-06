<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenant    = $request->user()->tenant;
        $tid       = $tenant->id;
        $lastMonth = now()->subMonth();

        $base = fn () => Invoice::where('tenant_id', $tid);

        // MongoDB stores dates as strings (Y-m-d), so use range queries instead
        // of whereYear/whereMonth which require BSON Date types
        $monthStart  = now()->startOfMonth()->format('Y-m-d');
        $monthEnd    = now()->endOfMonth()->format('Y-m-d');
        $lmStart     = $lastMonth->copy()->startOfMonth()->format('Y-m-d');
        $lmEnd       = $lastMonth->copy()->endOfMonth()->format('Y-m-d');

        $monthlyTotal = (clone $base())
            ->where('invoice_date', '>=', $monthStart)
            ->where('invoice_date', '<=', $monthEnd)
            ->sum('total_amount');

        $monthlyPaid = (clone $base())
            ->where('invoice_date', '>=', $monthStart)
            ->where('invoice_date', '<=', $monthEnd)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $lastMonthTotal = (clone $base())
            ->where('invoice_date', '>=', $lmStart)
            ->where('invoice_date', '<=', $lmEnd)
            ->sum('total_amount');

        // MongoDB-compatible: compute outstanding in PHP
        $totalOutstanding = (clone $base())
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->get(['total_amount', 'amount_paid'])
            ->sum(fn ($i) => $i->total_amount - $i->amount_paid);

        $overdueInvoices = (clone $base())
            ->with('customer:id,name')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->limit(5)
            ->get(['id', 'invoice_number', 'customer_id', 'due_date', 'total_amount', 'amount_paid', 'payment_status']);

        $overdueCount = (clone $base())
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        // MongoDB-compatible: top customers without JOIN — group in PHP
        $invoicesByCustomer = (clone $base())
            ->get(['customer_id', 'total_amount'])
            ->groupBy('customer_id');

        $customerIds = $invoicesByCustomer->keys()->all();
        $customerNames = Customer::whereIn('_id', $customerIds)
            ->get(['_id', 'name'])
            ->keyBy(fn ($c) => (string) $c->_id);

        $topCustomers = $invoicesByCustomer
            ->map(fn ($invoices, $cid) => [
                'id'            => $cid,
                'name'          => $customerNames[(string) $cid]?->name ?? 'Unknown',
                'total_billed'  => $invoices->sum('total_amount'),
                'invoice_count' => $invoices->count(),
            ])
            ->sortByDesc('total_billed')
            ->take(5)
            ->values();

        $momChange = $lastMonthTotal > 0
            ? round((($monthlyTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1)
            : null;

        // Last 12 months revenue chart data
        $monthlyChart = collect(range(11, 0))->map(function ($n) use ($base) {
            $date  = now()->subMonths($n);
            $start = $date->copy()->startOfMonth()->format('Y-m-d');
            $end   = $date->copy()->endOfMonth()->format('Y-m-d');

            $total = (clone $base())
                ->where('invoice_date', '>=', $start)
                ->where('invoice_date', '<=', $end)
                ->sum('total_amount');

            $cgst = (clone $base())
                ->where('invoice_date', '>=', $start)
                ->where('invoice_date', '<=', $end)
                ->sum('cgst_amount');

            $sgst = (clone $base())
                ->where('invoice_date', '>=', $start)
                ->where('invoice_date', '<=', $end)
                ->sum('sgst_amount');

            $igst = (clone $base())
                ->where('invoice_date', '>=', $start)
                ->where('invoice_date', '<=', $end)
                ->sum('igst_amount');

            return [
                'label'  => $date->format('M y'),
                'total'  => round($total, 2),
                'cgst'   => round($cgst, 2),
                'sgst'   => round($sgst, 2),
                'igst'   => round($igst, 2),
            ];
        })->values();

        // Tax liability totals (this month)
        $taxThisMonth = [
            'cgst' => (clone $base())
                ->where('invoice_date', '>=', $monthStart)
                ->where('invoice_date', '<=', $monthEnd)
                ->sum('cgst_amount'),
            'sgst' => (clone $base())
                ->where('invoice_date', '>=', $monthStart)
                ->where('invoice_date', '<=', $monthEnd)
                ->sum('sgst_amount'),
            'igst' => (clone $base())
                ->where('invoice_date', '>=', $monthStart)
                ->where('invoice_date', '<=', $monthEnd)
                ->sum('igst_amount'),
        ];

        return Inertia::render('Dashboard', [
            'stats' => [
                'monthly_total'     => $monthlyTotal,
                'monthly_paid'      => $monthlyPaid,
                'total_outstanding' => $totalOutstanding,
                'invoice_count'     => (clone $base())->count(),
                'overdue_count'     => $overdueCount,
                'last_month_total'  => $lastMonthTotal,
                'mom_change'        => $momChange,
            ],
            'recentInvoices'  => (clone $base())->with('customer:id,name')->latest()->limit(5)->get(),
            'overdueInvoices' => $overdueInvoices,
            'topCustomers'    => $topCustomers,
            'monthlyChart'    => $monthlyChart,
            'taxThisMonth'    => $taxThisMonth,
        ]);
    }
}
