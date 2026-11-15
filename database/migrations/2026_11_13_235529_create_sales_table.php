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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->index();
            $table->string('customer_name');
            $table->foreignId('seller_id')->constrained('sellers')->restrictOnDelete();
            $table->foreignId('deliverer_id')->nullable()->constrained('deliverers')->restrictOnDelete();
            $table->string('delivery_type', 30);   // immediate | scheduled_delivery | customer_pickup
            $table->string('status', 30);          // pending_delivery | delivered | canceled
            $table->dateTime('sold_at');
            $table->dateTime('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();


            // Fila de entregas pendentes e listagens por período
            $table->index(['status', 'sold_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
