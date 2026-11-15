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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained('floors')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 30);
            // Sempre positivo, exceto em manual_adjustment (delta com sinal)
            $table->decimal('quantity_m2', 10, 4);
            $table->decimal('physical_balance_after', 10, 4);
            $table->decimal('committed_balance_after', 10, 4);
            $table->string('document_number')->nullable()->index();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->restrictOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->restrictOnDelete();
            $table->text('reason')->nullable();
            // Tabela append-only: não há updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->index(['floor_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
