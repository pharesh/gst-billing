<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gstin', 15)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('state_code', 2)->nullable(); // GST state code (e.g. 27 for Maharashtra)
            $table->string('pincode', 10)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->string('signature')->nullable();
            $table->text('bank_details')->nullable(); // JSON: bank_name, account_no, ifsc, branch
            $table->enum('subscription_plan', ['free', 'starter', 'pro'])->default('free');
            $table->timestamp('subscription_ends_at')->nullable();
            $table->integer('invoice_count')->default(0);
            $table->string('invoice_prefix', 10)->default('INV');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
