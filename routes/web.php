<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminTenantController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RecurringInvoiceController;
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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Invoices
    Route::resource('invoices', InvoiceController::class)->except(['store']);
    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('plan.limit:invoice')->name('invoices.store');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/invoices/{invoice}/whatsapp', [InvoiceController::class, 'sendWhatsApp'])->name('invoices.whatsapp');
    Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'sendEmail'])->name('invoices.email');
    Route::post('/invoices/{invoice}/reminder', [InvoiceController::class, 'sendReminder'])->name('invoices.reminder');

    // Customers
    Route::resource('customers', CustomerController::class)->except(['create', 'show', 'edit', 'store']);
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('plan.limit:customer')->name('customers.store');
    Route::get('/customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');

    // Products
    Route::resource('products', ProductController::class)->except(['create', 'show', 'edit', 'store']);
    Route::post('/products', [ProductController::class, 'store'])->middleware('plan.limit:product')->name('products.store');

    // Payments
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // GST Reports
    Route::get('/reports', [GSTReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/gstr1', [GSTReportController::class, 'gstr1'])->name('reports.gstr1');
    Route::get('/reports/gstr3b', [GSTReportController::class, 'gstr3b'])->name('reports.gstr3b');
    Route::get('/reports/gstr1/download', [GSTReportController::class, 'downloadGSTR1'])->name('reports.gstr1.download');

    // Credit Notes
    Route::resource('credit-notes', CreditNoteController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    // Recurring Invoices
    Route::resource('recurring-invoices', RecurringInvoiceController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::post('/recurring-invoices/{recurringInvoice}/toggle', [RecurringInvoiceController::class, 'toggle'])->name('recurring-invoices.toggle');

    // Billing & Subscription
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/order', [BillingController::class, 'createOrder'])->name('billing.order');
    Route::post('/billing/verify', [BillingController::class, 'verifyPayment'])->name('billing.verify');
});

// Admin Panel
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Tenants
    Route::get('/tenants', [AdminTenantController::class, 'index'])->name('tenants.index');
    Route::post('/tenants', [AdminTenantController::class, 'store'])->name('tenants.store');
    Route::patch('/tenants/{tenant}', [AdminTenantController::class, 'update'])->name('tenants.update');
    Route::get('/tenants/{tenant}', [AdminTenantController::class, 'show'])->name('tenants.show');
    Route::post('/tenants/{tenant}/assign-plan', [AdminTenantController::class, 'assignPlan'])->name('tenants.assign-plan');
    Route::post('/tenants/{tenant}/toggle-suspend', [AdminTenantController::class, 'toggleSuspend'])->name('tenants.toggle-suspend');
    Route::delete('/tenants/{tenant}', [AdminTenantController::class, 'destroy'])->name('tenants.destroy');

    // Plans
    Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [AdminPlanController::class, 'store'])->name('plans.store');
    Route::patch('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [AdminPlanController::class, 'destroy'])->name('plans.destroy');
});

require __DIR__.'/auth.php';
