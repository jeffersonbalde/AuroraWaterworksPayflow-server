<?php
// database/migrations/2024_01_01_000001_create_bills_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('wws_id')->nullable();
            
            // Reading Information
            $table->date('reading_date');
            $table->date('due_date');
            $table->decimal('previous_reading', 10, 2);
            $table->decimal('present_reading', 10, 2);
            $table->decimal('consumption', 10, 2)->default(0);
            
            // Billing Information
            $table->decimal('amount', 10, 2);
            $table->decimal('penalty', 10, 2)->default(0);
            $table->decimal('total_payable', 10, 2)->default(0);
            
            // Restatement Fields
            $table->decimal('restated_amount', 10, 2)->nullable();
            $table->text('restatement_reason')->nullable();
            $table->string('authorization_code')->nullable();
            
            // Status and Metadata
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->string('meter_reader')->nullable();
            $table->boolean('online_meter_used')->default(false);
            
            // Payment Information
            $table->timestamp('paid_at')->nullable();
            $table->string('qr_number')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['reading_date']);
            $table->index(['due_date']);
            $table->index(['wws_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bills');
    }
};