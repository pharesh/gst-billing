<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenant   = $request->user()->tenant;
        $tid      = $tenant->id;
        $thisMonth = now()->month;
        $thisYear  = now()->year;
        $lastMonth = now()->subMonth();

        $base = fn () => Invoice::where('tenant_id', $tid);

        // This month stats
        $monthlyTotal = (clone $base())
            ->whereYear('invoice_date', $thisYear)
            ->whereMonth('invoice_date', $thisMonth)
            ->sum('total_amount');

        $monthlyPaid = (clone $base())
            ->whereYear('invoice_date', $thisYear)
            ->whereMonth('invoice_date', $thisMonth)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        // Last month for comparison
        $lastMonthTotal = (clone $base())
            ->whereYear('invoice_date', $lastMonth->year)
            ->whereMonth('invoice_date', $lastMonth->month)
            ->sum('total_amount');

        // Outstanding (all time balance due)
        $totalOutstanding = (clone $base())
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->selectRaw('SUM(total_amount - amount_paid) as bal')
            ->value('bal') ?? 0;

        // Overdue
        $overdueInvoices = (clone $base())
            ->with('customer:id,name')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date')
            ->limit(5)
            ->get(['id', 'invoice_number', 'customer_id', 'due_date', 'total_amount', 'amount_paid', 'payment_status']);

        $overdueCount = (clone $base())
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->count();

        // Top customers by total billed (all time)
        $topCustomers = Customer::where('customers.tenant_id', $tid)
            ->join('invoices', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.tenant_id', $tid)
            ->selectRaw('customers.id, customers.name, SUM(invoices.total_amount) as total_billed, COUNT(invoices.id) as invoice_count')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_billed')
            ->limit(5)
            ->get();

        // Month-over-month % change
        $momChange = $lastMonthTotal > 0
            ? round((($monthlyTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1)
            : null;

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
        ]);
    }
}
