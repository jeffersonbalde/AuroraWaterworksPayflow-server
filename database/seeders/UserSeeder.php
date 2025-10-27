<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create Admin
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@aurorawater.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create Staff
        User::create([
            'name' => 'Staff Member',
            'email' => 'staff@aurorawater.com',
            'password' => Hash::make('staff123'),
            'role' => 'staff',
            'status' => 'active',
        ]);

        // Create Sample Client (Pending)
        User::create([
            'wws_id' => '1375',
            'name' => 'CABAHUG BONIFACIO',
            'email' => 'client@example.com',
            'contact_number' => '09123456789',
            'address' => 'POBLACION AURORA',
            'password' => Hash::make('client123'),
            'role' => 'client',
            'status' => 'pending',
        ]);

        User::create([
            'wws_id' => '1377',
            'name' => 'Rejected Client',
            'email' => 'rejected@example.com',
            'contact_number' => '09123456781',
            'address' => 'POBLACION AURORA',
            'password' => Hash::make('client123'),
            'role' => 'client',
            'status' => 'rejected', // Add this line
        ]);

        // Create Active Client
        User::create([
            'wws_id' => '1376',
            'name' => 'Approved Client',
            'email' => 'approved@example.com',
            'contact_number' => '09123456780',
            'address' => 'POBLACION AURORA',
            'password' => Hash::make('client123'),
            'role' => 'client',
            'status' => 'active',
        ]);
    }
}
