<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('invoice_type', ['b2b', 'b2c', 'export'])->default('b2b');

            // Intrastate = CGST + SGST, Interstate = IGST
            $table->enum('supply_type', ['intrastate', 'interstate'])->default('intrastate');

            // Amounts
            $table->decimal('subtotal', 14, 2)->default(0);       // taxable value
            $table->decimal('cgst_amount', 14, 2)->default(0);
            $table->decimal('sgst_amount', 14, 2)->default(0);
            $table->decimal('igst_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);

            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->string('terms')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'invoice_date']);
            $table->index(['tenant_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
