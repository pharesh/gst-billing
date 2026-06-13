<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminTenantController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\GSTReportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// ─── Public Auth Routes ────────────────────────────────────────────────────────
Route::post('/auth/register',        [RegisteredUserController::class, 'store']);
Route::post('/auth/login',           [AuthenticatedSessionController::class, 'store']);
Route::post('/auth/verify-otp',      [OtpController::class, 'verify']);
Route::post('/auth/resend-otp',      [OtpController::class, 'resend']);
Route::post('/auth/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/auth/reset-password',  [NewPasswordController::class, 'store']);

// ─── Authenticated Routes ──────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Auth helpers
    Route::get('/auth/me',      [AuthenticatedSessionController::class, 'me']);
    Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy']);

    // Profile & Password
    Route::get('/profile',    [ProfileController::class, 'show']);
    Route::patch('/profile',  [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);
    Route::put('/password',   [PasswordController::class, 'update']);

    // Business Settings
    Route::get('/settings',   [SettingsController::class, 'show']);
    Route::patch('/settings', [SettingsController::class, 'update']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Invoices
    Route::get('/invoices',                                  [InvoiceController::class, 'index']);
    Route::get('/invoices/create',                           [InvoiceController::class, 'create']);
    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('plan.limit:invoice');
    // Static segment routes must be defined BEFORE /{invoice} wildcard routes
    Route::get('/invoices-export',                           [InvoiceController::class, 'export']);
    Route::post('/invoices/bulk-download',                   [InvoiceController::class, 'bulkDownload']);
    Route::post('/invoices/bulk-reminder',                   [InvoiceController::class, 'bulkReminder']);
    Route::get('/invoices/{invoice}',                        [InvoiceController::class, 'show']);
    Route::get('/invoices/{invoice}/edit',                   [InvoiceController::class, 'edit']);
    Route::patch('/invoices/{invoice}',                      [InvoiceController::class, 'update']);
    Route::delete('/invoices/{invoice}',                     [InvoiceController::class, 'destroy']);
    Route::get('/invoices/{invoice}/download',               [InvoiceController::class, 'download']);
    Route::post('/invoices/{invoice}/whatsapp',              [InvoiceController::class, 'sendWhatsApp']);
    Route::post('/invoices/{invoice}/email',                 [InvoiceController::class, 'sendEmail']);
    Route::post('/invoices/{invoice}/reminder',              [InvoiceController::class, 'sendReminder']);
    Route::post('/invoices/{invoice}/payment-link',          [InvoiceController::class, 'generatePaymentLink']);
    Route::post('/invoices/{invoice}/payment-link/sync',     [InvoiceController::class, 'syncPaymentLink']);
    Route::post('/invoices/{invoice}/irn',                   [InvoiceController::class, 'generateIRN']);
    Route::post('/invoices/{invoice}/irn/cancel',            [InvoiceController::class, 'cancelIRN']);

    // Payments
    Route::get('/payments',                     [PaymentController::class, 'index']);
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store']);
    Route::delete('/payments/{payment}',        [PaymentController::class, 'destroy']);
    Route::get('/payments-export',              [PaymentController::class, 'export']);

    // Customers
    Route::get('/customers',                           [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('plan.limit:customer');
    Route::get('/customers/{customer}',                [CustomerController::class, 'show']);
    Route::patch('/customers/{customer}',              [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}',             [CustomerController::class, 'destroy']);
    Route::get('/customers/{customer}/statement',      [CustomerController::class, 'statement']);
    Route::get('/customers-export',                    [CustomerController::class, 'export']);

    // Products
    Route::get('/products',              [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store'])->middleware('plan.limit:product');
    Route::get('/products/{product}',    [ProductController::class, 'show']);
    Route::patch('/products/{product}',  [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Suppliers
    Route::get('/suppliers',               [SupplierController::class, 'index']);
    Route::post('/suppliers',              [SupplierController::class, 'store']);
    Route::get('/suppliers/{supplier}',    [SupplierController::class, 'show']);
    Route::patch('/suppliers/{supplier}',  [SupplierController::class, 'update']);
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);

    // Quotations
    Route::get('/quotations',                          [QuotationController::class, 'index']);
    Route::get('/quotations/create',                   [QuotationController::class, 'create']);
    Route::post('/quotations',                         [QuotationController::class, 'store']);
    Route::get('/quotations/{quotation}',              [QuotationController::class, 'show']);
    Route::delete('/quotations/{quotation}',           [QuotationController::class, 'destroy']);
    Route::post('/quotations/{quotation}/status',      [QuotationController::class, 'updateStatus']);
    Route::post('/quotations/{quotation}/convert',     [QuotationController::class, 'convert']);
    Route::get('/quotations/{quotation}/download',     [QuotationController::class, 'download']);

    // Purchases / Bills
    Route::get('/purchases',                           [PurchaseController::class, 'index']);
    Route::get('/purchases/create',                    [PurchaseController::class, 'create']);
    Route::post('/purchases',                          [PurchaseController::class, 'store']);
    Route::get('/purchases/{purchase}',                [PurchaseController::class, 'show']);
    Route::delete('/purchases/{purchase}',             [PurchaseController::class, 'destroy']);
    Route::post('/purchases/{purchase}/mark-paid',     [PurchaseController::class, 'markPaid']);

    // Credit Notes
    Route::get('/credit-notes',                        [CreditNoteController::class, 'index']);
    Route::get('/credit-notes/create',                 [CreditNoteController::class, 'create']);
    Route::post('/credit-notes',                       [CreditNoteController::class, 'store']);
    Route::get('/credit-notes/{creditNote}',           [CreditNoteController::class, 'show']);
    Route::delete('/credit-notes/{creditNote}',        [CreditNoteController::class, 'destroy']);

    // Debit Notes
    Route::get('/debit-notes',                         [DebitNoteController::class, 'index']);
    Route::get('/debit-notes/create',                  [DebitNoteController::class, 'create']);
    Route::post('/debit-notes',                        [DebitNoteController::class, 'store']);
    Route::get('/debit-notes/{debitNote}',             [DebitNoteController::class, 'show']);
    Route::delete('/debit-notes/{debitNote}',          [DebitNoteController::class, 'destroy']);

    // Recurring Invoices
    Route::get('/recurring-invoices',                          [RecurringInvoiceController::class, 'index']);
    Route::get('/recurring-invoices/create',                   [RecurringInvoiceController::class, 'create']);
    Route::post('/recurring-invoices',                         [RecurringInvoiceController::class, 'store']);
    Route::delete('/recurring-invoices/{recurringInvoice}',    [RecurringInvoiceController::class, 'destroy']);
    Route::post('/recurring-invoices/{recurringInvoice}/toggle', [RecurringInvoiceController::class, 'toggle']);

    // GST Reports
    Route::get('/reports/gstr1',          [GSTReportController::class, 'gstr1']);
    Route::get('/reports/gstr3b',         [GSTReportController::class, 'gstr3b']);
    Route::get('/reports/aging',          [GSTReportController::class, 'aging']);
    Route::get('/reports/gstr1/download', [GSTReportController::class, 'downloadGSTR1']);

    // Billing & Subscription
    Route::get('/billing',          [BillingController::class, 'index']);
    Route::post('/billing/order',   [BillingController::class, 'createOrder']);
    Route::post('/billing/verify',  [BillingController::class, 'verifyPayment']);
});

// ─── Admin Routes ──────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'superadmin'])->prefix('admin')->group(function () {
    Route::get('/',          [AdminDashboardController::class, 'index']);
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/tenants',                                    [AdminTenantController::class, 'index']);
    Route::post('/tenants',                                   [AdminTenantController::class, 'store']);
    Route::get('/tenants/{tenant}',                           [AdminTenantController::class, 'show']);
    Route::patch('/tenants/{tenant}',                         [AdminTenantController::class, 'update']);
    Route::post('/tenants/{tenant}/assign-plan',              [AdminTenantController::class, 'assignPlan']);
    Route::post('/tenants/{tenant}/toggle-suspend',           [AdminTenantController::class, 'toggleSuspend']);
    Route::delete('/tenants/{tenant}',                        [AdminTenantController::class, 'destroy']);
    Route::get('/plans',                                      [AdminPlanController::class, 'index']);
    Route::post('/plans',                                     [AdminPlanController::class, 'store']);
    Route::patch('/plans/{plan}',                             [AdminPlanController::class, 'update']);
    Route::delete('/plans/{plan}',                            [AdminPlanController::class, 'destroy']);
    Route::get('/system-settings',                            [SystemSettingsController::class, 'index']);
    Route::put('/system-settings',                            [SystemSettingsController::class, 'bulkUpdate']);
    Route::put('/system-settings/{key}',                      [SystemSettingsController::class, 'update']);
});
