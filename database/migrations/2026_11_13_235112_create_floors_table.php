<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('reference')->nullable()->index();
            $table->string('description');
            $table->string('brand')->nullable();
            $table->decimal('m2_per_box', 10, 4)->nullable();
            $table->decimal('physical_stock', 10, 4)->default(0);
            $table->decimal('committed_stock', 10, 4)->default(0);
            $table->decimal('min_stock_alert', 10, 4)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('floors');
    }
};
