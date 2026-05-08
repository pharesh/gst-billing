<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Http\Request;
use App\Http\Controllers\GSTReportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $tenant = $request->user()->tenant;
        $tenantId = $tenant?->id;

        return Inertia::render('Dashboard', [
            'stats' => [
                'monthly_total' => \App\Models\Invoice::where('tenant_id', $tenantId)->whereMonth('invoice_date', now()->month)->sum('total_amount'),
                'monthly_paid' => \App\Models\Invoice::where('tenant_id', $tenantId)->whereMonth('invoice_date', now()->month)->where('payment_status', 'paid')->sum('total_amount'),
                'total_outstanding' => \App\Models\Invoice::where('tenant_id', $tenantId)->whereIn('payment_status', ['unpaid', 'partial'])->selectRaw('SUM(total_amount - amount_paid)')->value('SUM(total_amount - amount_paid)'),
                'invoice_count' => \App\Models\Invoice::where('tenant_id', $tenantId)->count(),
            ],
            'recentInvoices' => \App\Models\Invoice::with('customer')->where('tenant_id', $tenantId)->latest()->limit(5)->get(),
        ]);
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/invoices/{invoice}/whatsapp', [InvoiceController::class, 'sendWhatsApp'])->name('invoices.whatsapp');
    Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'sendEmail'])->name('invoices.email');
    Route::post('/invoices/{invoice}/reminder', [InvoiceController::class, 'sendReminder'])->name('invoices.reminder');

    // Customers
    Route::resource('customers', CustomerController::class)->except(['create', 'show', 'edit']);

    // Products
    Route::resource('products', ProductController::class)->except(['create', 'show', 'edit']);

    // Payments
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // GST Reports
    Route::get('/reports', [GSTReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/gstr1', [GSTReportController::class, 'gstr1'])->name('reports.gstr1');
    Route::get('/reports/gstr3b', [GSTReportController::class, 'gstr3b'])->name('reports.gstr3b');
    Route::get('/reports/gstr1/download', [GSTReportController::class, 'downloadGSTR1'])->name('reports.gstr1.download');
});

require __DIR__.'/auth.php';
