<?php
// database/migrations/2024_01_01_000002_create_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            $table->string('wws_id')->nullable();
            
            // Payment Information
            $table->decimal('amount_paid', 10, 2);
            $table->string('qr_number');
            $table->date('qr_date');
            $table->decimal('balance', 10, 2)->default(0);
            
            // Payment Method
            $table->enum('payment_method', ['online', 'over_the_counter']);
            $table->string('electronic_qr_number')->nullable(); // For online payments
            $table->decimal('electronic_amount', 10, 2)->nullable(); // For online payments
            
            // Payment Gateway Fields
            $table->string('payment_gateway')->nullable()->default('demo');
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->text('gateway_response')->nullable();
            $table->enum('payment_status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Status and Metadata
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->string('collector_name')->nullable(); // For over-the-counter payments
            $table->timestamp('payment_date')->useCurrent();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'payment_date']);
            $table->index(['bill_id']);
            $table->index(['qr_number']);
            $table->index(['payment_method']);
            $table->index(['payment_status']);
            $table->index(['gateway_reference']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};