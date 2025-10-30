<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user with avatar
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@aurorawater.com',
            'password' => Hash::make('admin123'),
            'contact_number' => '09123456789',
            'address' => 'Aurora Waterworks Main Office',
            'avatar' => 'https://ui-avatars.com/api/?name=System+Admin&background=336B34&color=fff&size=128',
            'role' => 'admin',
            'status' => 'active',
            'approved_by' => 'System',
            'approved_at' => now(),
        ]);

        // Staff user with avatar - UPDATED WITH STAFF FIELDS
        User::create([
            'name' => 'Staff Member',
            'email' => 'staff@aurorawater.com',
            'password' => Hash::make('staff123'),
            'contact_number' => '09123456788',
            'address' => 'Aurora Waterworks Office',
            // Staff-specific fields
            'position' => 'General Staff',
            'created_by' => 'System Admin',
            'staff_notes' => 'Initial staff account for system testing',
            // End staff-specific fields
            'avatar' => 'https://ui-avatars.com/api/?name=Staff+Member&background=0D8ABC&color=fff&size=128',
            'role' => 'staff',
            'status' => 'active',
            'approved_by' => 'System Admin',
            'approved_at' => now(),
        ]);

        // Sample Client (Pending) - no avatar
        User::create([
            'wws_id' => '1375',
            'name' => 'CABAHUG BONIFACIO',
            'email' => 'client@example.com',
            'contact_number' => '09123456789',
            'address' => 'POBLACION AURORA',
            'password' => Hash::make('client123'),
            'avatar' => null, // Clients don't have avatars
            'role' => 'client',
            'status' => 'pending',
        ]);

        // Rejected Client - no avatar
        User::create([
            'wws_id' => '1377',
            'name' => 'Rejected Client',
            'email' => 'rejected@example.com',
            'contact_number' => '09123456781',
            'address' => 'POBLACION AURORA',
            'password' => Hash::make('client123'),
            'avatar' => null,
            'role' => 'client',
            'status' => 'rejected',
            'rejected_by' => 'System Admin',
            'rejected_at' => now(),
            'rejection_reason' => 'Invalid WWS ID provided',
        ]);

        // Active Client - no avatar
        User::create([
            'wws_id' => '1376',
            'name' => 'Approved Client',
            'email' => 'approved@example.com',
            'contact_number' => '09123456780',
            'address' => 'POBLACION AURORA',
            'password' => Hash::make('client123'),
            'avatar' => null,
            'role' => 'client',
            'status' => 'active',
            'approved_by' => 'System Admin',
            'approved_at' => now(),
        ]);

        // Additional Staff Members with avatars - UPDATED WITH STAFF FIELDS
        $staffMembers = [
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@aurorawater.com',
                'contact_number' => '09987654321',
                'address' => 'Aurora Waterworks Billing Dept',
                'position' => 'Billing Staff',
                'staff_notes' => 'Handles client billing and payment processing'
            ],
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan.delacruz@aurorawater.com',
                'contact_number' => '09987654322',
                'address' => 'Aurora Waterworks Customer Service',
                'position' => 'Customer Service Staff',
                'staff_notes' => 'Manages customer inquiries and support'
            ],
            [
                'name' => 'Roberto Garcia',
                'email' => 'roberto.garcia@aurorawater.com',
                'contact_number' => '09987654323',
                'address' => 'Aurora Waterworks Meter Reading',
                'position' => 'Meter Reader Supervisor',
                'staff_notes' => 'Oversees meter reading operations and team'
            ]
        ];

        foreach ($staffMembers as $staff) {
            User::create([
                'name' => $staff['name'],
                'email' => $staff['email'],
                'password' => Hash::make('staff123'),
                'contact_number' => $staff['contact_number'],
                'address' => $staff['address'],
                // Staff-specific fields
                'position' => $staff['position'],
                'created_by' => 'System Admin',
                'staff_notes' => $staff['staff_notes'],
                // End staff-specific fields
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($staff['name']) . '&background=0D8ABC&color=fff&size=128',
                'role' => 'staff',
                'status' => 'active',
                'approved_by' => 'System Admin',
                'approved_at' => now(),
            ]);
        }

        // Create 30+ Pending Clients for Approval Tab Testing - no avatars
        $pendingClients = [
            ['wws_id' => '1401', 'name' => 'ABELLA RICARDO', 'email' => 'abella.ricardo@example.com', 'contact_number' => '09120000001', 'address' => 'ZAMORA AURORA'],
            ['wws_id' => '1402', 'name' => 'BACALSO MARIA', 'email' => 'bacalso.maria@example.com', 'contact_number' => '09120000002', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1403', 'name' => 'CABAHUG JUAN', 'email' => 'cabahug.juan@example.com', 'contact_number' => '09120000003', 'address' => 'SAN JOSE AURORA'],
            ['wws_id' => '1404', 'name' => 'DALISAY CARLOS', 'email' => 'dalisay.carlos@example.com', 'contact_number' => '09120000004', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1405', 'name' => 'ESTEVES ANTONIO', 'email' => 'esteves.antonio@example.com', 'contact_number' => '09120000005', 'address' => 'ZAMORA AURORA'],
            ['wws_id' => '1406', 'name' => 'FLORES BEATRIZ', 'email' => 'flores.beatriz@example.com', 'contact_number' => '09120000006', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1407', 'name' => 'GARCIA ROMEO', 'email' => 'garcia.romeo@example.com', 'contact_number' => '09120000007', 'address' => 'SAN JOSE AURORA'],
            ['wws_id' => '1408', 'name' => 'HERNANDEZ LOURDES', 'email' => 'hernandez.lourdes@example.com', 'contact_number' => '09120000008', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1409', 'name' => 'IBANEZ FERNANDO', 'email' => 'ibanez.fernando@example.com', 'contact_number' => '09120000009', 'address' => 'ZAMORA AURORA'],
            ['wws_id' => '1410', 'name' => 'JAVIER SUSANA', 'email' => 'javier.susana@example.com', 'contact_number' => '09120000010', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1411', 'name' => 'KINTANAR ROBERTO', 'email' => 'kintanar.roberto@example.com', 'contact_number' => '09120000011', 'address' => 'SAN JOSE AURORA'],
            ['wws_id' => '1412', 'name' => 'LIM MIGUEL', 'email' => 'lim.miguel@example.com', 'contact_number' => '09120000012', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1413', 'name' => 'MARTINEZ CONSUELO', 'email' => 'martinez.consuelo@example.com', 'contact_number' => '09120000013', 'address' => 'ZAMORA AURORA'],
            ['wws_id' => '1414', 'name' => 'NAVARRO PEDRO', 'email' => 'navarro.pedro@example.com', 'contact_number' => '09120000014', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1415', 'name' => 'OCAMPO TERESITA', 'email' => 'ocampo.teresita@example.com', 'contact_number' => '09120000015', 'address' => 'SAN JOSE AURORA'],
            ['wws_id' => '1416', 'name' => 'PEREZ RAMON', 'email' => 'perez.ramon@example.com', 'contact_number' => '09120000016', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1417', 'name' => 'QUINTO GLORIA', 'email' => 'quinto.gloria@example.com', 'contact_number' => '09120000017', 'address' => 'ZAMORA AURORA'],
            ['wws_id' => '1418', 'name' => 'RAMOS ALBERTO', 'email' => 'ramos.alberto@example.com', 'contact_number' => '09120000018', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1419', 'name' => 'SANTOS ISABEL', 'email' => 'santos.isabel@example.com', 'contact_number' => '09120000019', 'address' => 'SAN JOSE AURORA'],
            ['wws_id' => '1420', 'name' => 'TORRES RAFAEL', 'email' => 'torres.rafael@example.com', 'contact_number' => '09120000020', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1421', 'name' => 'URBANO MERCEDES', 'email' => 'urbano.mercedes@example.com', 'contact_number' => '09120000021', 'address' => 'ZAMORA AURORA'],
            ['wws_id' => '1422', 'name' => 'VALDEZ ENRIQUE', 'email' => 'valdez.enrique@example.com', 'contact_number' => '09120000022', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1423', 'name' => 'YBAÑEZ CARMEN', 'email' => 'ybanez.carmen@example.com', 'contact_number' => '09120000023', 'address' => 'SAN JOSE AURORA'],
            ['wws_id' => '1424', 'name' => 'ZABALA RODOLFO', 'email' => 'zabala.rodolfo@example.com', 'contact_number' => '09120000024', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1425', 'name' => 'ALCANTARA VIRGINIA', 'email' => 'alcantara.virginia@example.com', 'contact_number' => '09120000025', 'address' => 'ZAMORA AURORA'],
            ['wws_id' => '1426', 'name' => 'BARREDO ARTURO', 'email' => 'barredo.arturo@example.com', 'contact_number' => '09120000026', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1427', 'name' => 'CASTILLO MARCELA', 'email' => 'castillo.marcela@example.com', 'contact_number' => '09120000027', 'address' => 'SAN JOSE AURORA'],
            ['wws_id' => '1428', 'name' => 'DELGADO FELIX', 'email' => 'delgado.felix@example.com', 'contact_number' => '09120000028', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1429', 'name' => 'ESPINOSA ROSARIO', 'email' => 'espinosa.rosario@example.com', 'contact_number' => '09120000029', 'address' => 'ZAMORA AURORA'],
            ['wws_id' => '1430', 'name' => 'FERNANDEZ LEONARDO', 'email' => 'fernandez.leonardo@example.com', 'contact_number' => '09120000030', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1431', 'name' => 'GONZALES REYNALDO', 'email' => 'gonzales.reynaldo@example.com', 'contact_number' => '09120000031', 'address' => 'SAN JOSE AURORA'],
            ['wws_id' => '1432', 'name' => 'HERRERA JOSEFINA', 'email' => 'herrera.josefina@example.com', 'contact_number' => '09120000032', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1433', 'name' => 'IGNACIO SALVADOR', 'email' => 'ignacio.salvador@example.com', 'contact_number' => '09120000033', 'address' => 'ZAMORA AURORA'],
            ['wws_id' => '1434', 'name' => 'JIMENEZ CORAZON', 'email' => 'jimenez.corazon@example.com', 'contact_number' => '09120000034', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1435', 'name' => 'LOPEZ MARGARITA', 'email' => 'lopez.margarita@example.com', 'contact_number' => '09120000035', 'address' => 'SAN JOSE AURORA'],
        ];

        foreach ($pendingClients as $client) {
            User::create([
                'wws_id' => $client['wws_id'],
                'name' => $client['name'],
                'email' => $client['email'],
                'contact_number' => $client['contact_number'],
                'address' => $client['address'],
                'password' => Hash::make('password123'),
                'avatar' => null, // No avatars for clients
                'role' => 'client',
                'status' => 'pending',
                'created_at' => now()->subDays(rand(1, 30))
            ]);
        }

        // Add some additional rejected users for variety - no avatars
        $rejectedClients = [
            ['wws_id' => '1501', 'name' => 'MORALES RICARDO', 'email' => 'morales.ricardo@example.com', 'contact_number' => '09120000036', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1502', 'name' => 'NUNEZ CARMELITA', 'email' => 'nunez.carmelita@example.com', 'contact_number' => '09120000037', 'address' => 'SAN JOSE AURORA'],
            ['wws_id' => '1503', 'name' => 'ORTEGA FELIPE', 'email' => 'ortega.felipe@example.com', 'contact_number' => '09120000038', 'address' => 'ZAMORA AURORA'],
        ];

        foreach ($rejectedClients as $client) {
            User::create([
                'wws_id' => $client['wws_id'],
                'name' => $client['name'],
                'email' => $client['email'],
                'contact_number' => $client['contact_number'],
                'address' => $client['address'],
                'password' => Hash::make('password123'),
                'avatar' => null,
                'role' => 'client',
                'status' => 'rejected',
                'rejected_by' => 'System Admin',
                'rejected_at' => now()->subDays(rand(1, 15)),
                'rejection_reason' => $this->getRandomRejectionReason(),
            ]);
        }

        // Add some additional active users - no avatars
        $activeClients = [
            ['wws_id' => '1601', 'name' => 'ORTIZ MANUEL', 'email' => 'ortiz.manuel@example.com', 'contact_number' => '09120000039', 'address' => 'POBLACION AURORA'],
            ['wws_id' => '1602', 'name' => 'PASCUAL ELENA', 'email' => 'pascual.elena@example.com', 'contact_number' => '09120000040', 'address' => 'ZAMORA AURORA'],
            ['wws_id' => '1603', 'name' => 'QUIZON RAUL', 'email' => 'quizon.raul@example.com', 'contact_number' => '09120000041', 'address' => 'SAN JOSE AURORA'],
            ['wws_id' => '1604', 'name' => 'REYES LILIA', 'email' => 'reyes.lilia@example.com', 'contact_number' => '09120000042', 'address' => 'POBLACION AURORA'],
        ];

        foreach ($activeClients as $client) {
            User::create([
                'wws_id' => $client['wws_id'],
                'name' => $client['name'],
                'email' => $client['email'],
                'contact_number' => $client['contact_number'],
                'address' => $client['address'],
                'password' => Hash::make('password123'),
                'avatar' => null,
                'role' => 'client',
                'status' => 'active',
                'approved_by' => 'System Admin',
                'approved_at' => now()->subDays(rand(1, 60)),
            ]);
        }
    }

    /**
     * Generate random rejection reasons
     */
    private function getRandomRejectionReason()
    {
        $reasons = [
            'Invalid WWS ID provided',
            'WWS ID does not match our records',
            'Duplicate account registration',
            'Incomplete personal information',
            'Suspected fraudulent registration',
            'WWS ID already associated with another account',
            'Document verification failed',
            'Address does not match service area',
        ];

        return $reasons[array_rand($reasons)];
    }
}