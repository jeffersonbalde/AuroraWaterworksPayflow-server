<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('wws_id')->unique()->nullable(); // Only for clients
            $table->string('name');
            $table->string('email')->unique();
            $table->string('contact_number')->nullable();
            $table->text('address')->nullable();
            $table->enum('role', ['admin', 'staff', 'client'])->default('client');
            $table->enum('status', ['active', 'inactive', 'pending', 'rejected'])->default('pending'); 
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};