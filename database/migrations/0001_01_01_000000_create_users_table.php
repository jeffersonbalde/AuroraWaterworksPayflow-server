<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_all_auth_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('wws_id')->unique()->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('contact_number')->nullable();
            $table->text('address')->nullable();
            
            // Customer-specific columns
            $table->enum('service', ['residential', 'commercial', 'institutional'])->nullable();
            $table->date('connection_date')->nullable();
            
            // Staff-specific columns
            $table->string('position')->nullable();
            $table->string('created_by')->nullable();
            $table->text('staff_notes')->nullable();
            
            // Deactivation fields
            $table->text('deactivate_reason')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('deactivated_by')->nullable();
            
            $table->string('avatar')->nullable();
            $table->enum('role', ['admin', 'staff', 'client'])->default('client');
            $table->enum('status', ['active', 'inactive', 'pending', 'rejected', 'delinquent'])->default('pending');
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            
            // Add index for better performance
            $table->index(['role', 'status']);
            $table->index(['status']); // For filtering by status
            $table->index(['service']); // For filtering by service type
        });

        // Password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};