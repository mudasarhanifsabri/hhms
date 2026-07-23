<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        User::updateOrCreate(['email' => 'admin@gmail.com'], [
            'name' => 'Mudasar Hanif',
            'phone' => '123456789',
            'role' => 'admin',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Agent User
        User::updateOrCreate(['email' => 'agent@gmail.com'], [
            'name' => 'Sheeza Shakeel',
            'phone' => '123456789',
            'role' => 'agent',
            'password' => Hash::make('password'),
            'agent_commission' => 5.00,
            'is_active' => true,
        ]);

        // Landlord User
        User::updateOrCreate(['email' => 'landlord@gmail.com'], [
            'name' => 'Fakhar Zaman',
            'phone' => '123456789',
            'role' => 'landlord',
            'password' => Hash::make('password'),
            'bank_name' => 'ABC Bank',
            'bank_account_holder' => 'Landlord User',
            'bank_account_number' => '987654321',
            'bank_account_type' => 'Current Account',
            'swift_code' => 'ABCDXYZ',
            'iban' => 'AE123456789012345678901',
            'bank_branch' => 'Main Branch',
            'is_active' => true,
        ]);

        // Tenant User
        User::updateOrCreate(['email' => 'tenant@gmail.com'], [
            'name' => 'Ahmed Khan',
            'phone' => '1234567893',
            'role' => 'tenant',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Maintainer User
        User::updateOrCreate(['email' => 'maintainer@gmail.com'], [
            'name' => 'Ayyan Khan',
            'phone' => '1234567894',
            'role' => 'maintainer',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }
}
