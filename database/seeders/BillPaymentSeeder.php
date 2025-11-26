<?php
// database/seeders/BillPaymentSeeder.php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BillPaymentSeeder extends Seeder
{
    public function run()
    {
        $client1 = User::where('email', 'client@aurorawater.com')->first();
        $client2 = User::where('email', 'maria@aurorawater.com')->first();

        if (!$client1 || !$client2) {
            $this->command->error('Client users not found. Please run UserSeeder first.');
            return;
        }

        // Bills for Client 1 (John Client)
        $client1Bills = [
            // Paid bill from 3 months ago
            [
                'user_id' => $client1->id,
                'wws_id' => $client1->wws_id,
                'reading_date' => Carbon::now()->subMonths(3)->startOfMonth(),
                'due_date' => Carbon::now()->subMonths(3)->startOfMonth()->addDays(15),
                'previous_reading' => 1000.00,
                'present_reading' => 1035.00,
                'consumption' => 35.00,
                'amount' => 525.00,
                'penalty' => 0.00,
                'total_payable' => 525.00,
                'status' => 'paid',
                'meter_reader' => 'Reader A',
                'online_meter_used' => true,
                'paid_at' => Carbon::now()->subMonths(3)->startOfMonth()->addDays(10),
                'qr_number' => 'QR' . uniqid(),
                'created_at' => Carbon::now()->subMonths(3),
                'updated_at' => Carbon::now()->subMonths(3)->addDays(10),
            ],
            // Paid bill from 2 months ago
            [
                'user_id' => $client1->id,
                'wws_id' => $client1->wws_id,
                'reading_date' => Carbon::now()->subMonths(2)->startOfMonth(),
                'due_date' => Carbon::now()->subMonths(2)->startOfMonth()->addDays(15),
                'previous_reading' => 1035.00,
                'present_reading' => 1070.00,
                'consumption' => 35.00,
                'amount' => 525.00,
                'penalty' => 0.00,
                'total_payable' => 525.00,
                'status' => 'paid',
                'meter_reader' => 'Reader B',
                'online_meter_used' => false,
                'paid_at' => Carbon::now()->subMonths(2)->startOfMonth()->addDays(12),
                'qr_number' => 'QR' . uniqid(),
                'created_at' => Carbon::now()->subMonths(2),
                'updated_at' => Carbon::now()->subMonths(2)->addDays(12),
            ],
            // Paid bill from last month
            [
                'user_id' => $client1->id,
                'wws_id' => $client1->wws_id,
                'reading_date' => Carbon::now()->subMonth()->startOfMonth(),
                'due_date' => Carbon::now()->subMonth()->startOfMonth()->addDays(15),
                'previous_reading' => 1070.00,
                'present_reading' => 1105.00,
                'consumption' => 35.00,
                'amount' => 525.00,
                'penalty' => 0.00,
                'total_payable' => 525.00,
                'status' => 'paid',
                'meter_reader' => 'Reader C',
                'online_meter_used' => true,
                'paid_at' => Carbon::now()->subMonth()->startOfMonth()->addDays(8),
                'qr_number' => 'QR' . uniqid(),
                'created_at' => Carbon::now()->subMonth(),
                'updated_at' => Carbon::now()->subMonth()->addDays(8),
            ],
            // Current pending bill
            [
                'user_id' => $client1->id,
                'wws_id' => $client1->wws_id,
                'reading_date' => Carbon::now()->startOfMonth(),
                'due_date' => Carbon::now()->startOfMonth()->addDays(15),
                'previous_reading' => 1105.00,
                'present_reading' => 1140.00,
                'consumption' => 35.00,
                'amount' => 525.00,
                'penalty' => 0.00,
                'total_payable' => 525.00,
                'status' => 'pending',
                'meter_reader' => 'Reader D',
                'online_meter_used' => true,
                'qr_number' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            // Overdue bill
            [
                'user_id' => $client1->id,
                'wws_id' => $client1->wws_id,
                'reading_date' => Carbon::now()->subMonths(4)->startOfMonth(),
                'due_date' => Carbon::now()->subMonths(3)->endOfMonth(),
                'previous_reading' => 965.00,
                'present_reading' => 1000.00,
                'consumption' => 35.00,
                'amount' => 525.00,
                'penalty' => 52.50,
                'total_payable' => 577.50,
                'status' => 'pending',
                'meter_reader' => 'Reader E',
                'online_meter_used' => false,
                'qr_number' => null,
                'created_at' => Carbon::now()->subMonths(4),
                'updated_at' => Carbon::now()->subMonths(4),
            ],
        ];

        // Bills for Client 2 (Maria Santos - Commercial)
        $client2Bills = [
            // Paid bill from 2 months ago
            [
                'user_id' => $client2->id,
                'wws_id' => $client2->wws_id,
                'reading_date' => Carbon::now()->subMonths(2)->startOfMonth(),
                'due_date' => Carbon::now()->subMonths(2)->startOfMonth()->addDays(15),
                'previous_reading' => 5000.00,
                'present_reading' => 5150.00,
                'consumption' => 150.00,
                'amount' => 2250.00,
                'penalty' => 0.00,
                'total_payable' => 2250.00,
                'status' => 'paid',
                'meter_reader' => 'Reader F',
                'online_meter_used' => true,
                'paid_at' => Carbon::now()->subMonths(2)->startOfMonth()->addDays(10),
                'qr_number' => 'QR' . uniqid(),
                'created_at' => Carbon::now()->subMonths(2),
                'updated_at' => Carbon::now()->subMonths(2)->addDays(10),
            ],
            // Paid bill from last month
            [
                'user_id' => $client2->id,
                'wws_id' => $client2->wws_id,
                'reading_date' => Carbon::now()->subMonth()->startOfMonth(),
                'due_date' => Carbon::now()->subMonth()->startOfMonth()->addDays(15),
                'previous_reading' => 5150.00,
                'present_reading' => 5320.00,
                'consumption' => 170.00,
                'amount' => 2550.00,
                'penalty' => 0.00,
                'total_payable' => 2550.00,
                'status' => 'paid',
                'meter_reader' => 'Reader G',
                'online_meter_used' => false,
                'paid_at' => Carbon::now()->subMonth()->startOfMonth()->addDays(12),
                'qr_number' => 'QR' . uniqid(),
                'created_at' => Carbon::now()->subMonth(),
                'updated_at' => Carbon::now()->subMonth()->addDays(12),
            ],
            // Current pending bill
            [
                'user_id' => $client2->id,
                'wws_id' => $client2->wws_id,
                'reading_date' => Carbon::now()->startOfMonth(),
                'due_date' => Carbon::now()->startOfMonth()->addDays(15),
                'previous_reading' => 5320.00,
                'present_reading' => 5500.00,
                'consumption' => 180.00,
                'amount' => 2700.00,
                'penalty' => 0.00,
                'total_payable' => 2700.00,
                'status' => 'pending',
                'meter_reader' => 'Reader H',
                'online_meter_used' => true,
                'qr_number' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        // Create bills and payments for client 1
        foreach ($client1Bills as $billData) {
            $bill = Bill::create($billData);

            // Create payment for paid bills
            if ($bill->status === 'paid') {
                Payment::create([
                    'user_id' => $client1->id,
                    'bill_id' => $bill->id,
                    'wws_id' => $client1->wws_id,
                    'amount_paid' => (float) $bill->total_payable,
                    'qr_number' => $bill->qr_number,
                    'qr_date' => $bill->paid_at,
                    'balance' => 0.00,
                    'payment_method' => 'online',
                    'payment_gateway' => 'demo',
                    'electronic_qr_number' => 'EQR' . uniqid(),
                    'electronic_amount' => (float) $bill->total_payable,
                    'payment_status' => 'completed',
                    'status' => 'completed',
                    'payment_date' => $bill->paid_at,
                    'processed_at' => $bill->paid_at,
                    'created_at' => $bill->paid_at,
                    'updated_at' => $bill->paid_at,
                ]);
            }
        }

        // Create bills and payments for client 2
        foreach ($client2Bills as $billData) {
            $bill = Bill::create($billData);

            // Create payment for paid bills
            if ($bill->status === 'paid') {
                Payment::create([
                    'user_id' => $client2->id,
                    'bill_id' => $bill->id,
                    'wws_id' => $client2->wws_id,
                    'amount_paid' => (float) $bill->total_payable,
                    'qr_number' => $bill->qr_number,
                    'qr_date' => $bill->paid_at,
                    'balance' => 0.00,
                    'payment_method' => 'online',
                    'payment_gateway' => 'demo',
                    'electronic_qr_number' => 'EQR' . uniqid(),
                    'electronic_amount' => (float) $bill->total_payable,
                    'payment_status' => 'completed',
                    'status' => 'completed',
                    'payment_date' => $bill->paid_at,
                    'processed_at' => $bill->paid_at,
                    'created_at' => $bill->paid_at,
                    'updated_at' => $bill->paid_at,
                ]);
            }
        }

        $this->command->info('Sample bills and payments created successfully!');
        $this->command->info('Admin Login: admin@aurorawater.com / password');
        $this->command->info('Staff Login: staff@aurorawater.com / password');
        $this->command->info('Client 1 Login: client@aurorawater.com / password');
        $this->command->info('Client 2 Login: maria@aurorawater.com / password');
        $this->command->info('Pending User: pending@aurorawater.com / password');
    }
}