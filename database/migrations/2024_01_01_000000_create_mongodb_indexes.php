<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        // users: unique email
        Schema::connection('mongodb')->table('users', function (Blueprint $collection) {
            $collection->unique('email');
        });

        // plans: unique slug
        Schema::connection('mongodb')->table('plans', function (Blueprint $collection) {
            $collection->unique('slug');
        });

        // customers: fast lookup by tenant
        Schema::connection('mongodb')->table('customers', function (Blueprint $collection) {
            $collection->index('tenant_id');
        });

        // products: fast lookup by tenant
        Schema::connection('mongodb')->table('products', function (Blueprint $collection) {
            $collection->index('tenant_id');
        });

        // invoices: tenant + date queries, status queries, unique invoice number per tenant
        Schema::connection('mongodb')->table('invoices', function (Blueprint $collection) {
            $collection->index(['tenant_id', 'invoice_date']);
            $collection->index(['tenant_id', 'payment_status']);
            $collection->unique(['tenant_id', 'invoice_number']);
        });

        // invoice_items: lookup by invoice
        Schema::connection('mongodb')->table('invoice_items', function (Blueprint $collection) {
            $collection->index('invoice_id');
        });

        // payments: lookup by tenant and invoice
        Schema::connection('mongodb')->table('payments', function (Blueprint $collection) {
            $collection->index('tenant_id');
            $collection->index('invoice_id');
        });

        // subscriptions: status queries per tenant
        Schema::connection('mongodb')->table('subscriptions', function (Blueprint $collection) {
            $collection->index(['tenant_id', 'status']);
        });

        // credit_notes: date queries per tenant
        Schema::connection('mongodb')->table('credit_notes', function (Blueprint $collection) {
            $collection->index(['tenant_id', 'credit_note_date']);
        });

        // credit_note_items: lookup by credit note
        Schema::connection('mongodb')->table('credit_note_items', function (Blueprint $collection) {
            $collection->index('credit_note_id');
        });

        // recurring_invoices: active schedule queries
        Schema::connection('mongodb')->table('recurring_invoices', function (Blueprint $collection) {
            $collection->index(['tenant_id', 'is_active', 'next_run_date']);
        });

        // suppliers: lookup by tenant
        Schema::connection('mongodb')->table('suppliers', function (Blueprint $collection) {
            $collection->index('tenant_id');
        });
    }

    public function down(): void
    {
        // Collections in MongoDB are dropped automatically when empty
        // or can be dropped explicitly:
        foreach ([
            'users', 'plans', 'customers', 'products', 'invoices',
            'invoice_items', 'payments', 'subscriptions', 'credit_notes',
            'credit_note_items', 'recurring_invoices', 'suppliers',
        ] as $collection) {
            Schema::connection('mongodb')->dropIfExists($collection);
        }
    }
};
