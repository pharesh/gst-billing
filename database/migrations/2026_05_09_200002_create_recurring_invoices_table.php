<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->enum('frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();
            $table->enum('invoice_type', ['b2b', 'b2c', 'export'])->default('b2b');
            $table->enum('supply_type', ['intrastate', 'interstate'])->default('intrastate');
            $table->integer('due_days')->default(15);
            $table->text('notes')->nullable();
            $table->string('terms')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('items');
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'next_run_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
