<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('hsn_sac_code', 20)->nullable(); // HSN for goods, SAC for services
            $table->enum('type', ['goods', 'service'])->default('goods');
            $table->string('unit', 20)->default('Nos'); // Nos, Kgs, Ltr, Hrs, etc.
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('gst_rate', 5, 2)->default(18); // 0, 5, 12, 18, 28
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
