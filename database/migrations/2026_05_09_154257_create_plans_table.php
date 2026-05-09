<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->integer('invoice_limit')->default(10);   // -1 = unlimited
            $table->integer('customer_limit')->default(10);  // -1 = unlimited
            $table->integer('product_limit')->default(10);   // -1 = unlimited
            $table->boolean('whatsapp_enabled')->default(false);
            $table->boolean('gstr_export')->default(false);
            $table->boolean('pdf_download')->default(true);
            $table->boolean('multi_user')->default(false);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
