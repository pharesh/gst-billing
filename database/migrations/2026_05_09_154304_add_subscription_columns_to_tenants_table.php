<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete()->after('id');
            $table->integer('monthly_invoice_count')->default(0)->after('invoice_count');
            $table->timestamp('monthly_count_reset_at')->nullable()->after('monthly_invoice_count');
            $table->boolean('is_suspended')->default(false)->after('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'monthly_invoice_count', 'monthly_count_reset_at', 'is_suspended']);
        });
    }
};
