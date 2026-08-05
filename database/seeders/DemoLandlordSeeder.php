<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoLandlordSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'demo.owner@pattern.ae'],
            [
                'name' => 'Demo Owner Bank Details',
                'name_ar' => 'مالك تجريبي',
                'phone' => '+971 50 555 0101',
                'dob' => '1985-01-15',
                'eid_passport_no' => '784-1985-1234567-1',
                'nationality' => 'Emirati',
                'gender' => 'Male',
                'id_expiry_date' => '2029-01-14',
                'address' => 'Dubai, United Arab Emirates',
                'bank_name' => 'Emirates NBD',
                'bank_account_holder' => 'Demo Owner Bank Details',
                'bank_account_number' => '1234567890',
                'bank_account_type' => 'Current Account',
                'swift_code' => 'EBILAEAD',
                'iban' => 'AE070331234567890123456',
                'bank_branch' => 'Dubai Marina',
                'password' => Hash::make('password'),
                'role' => 'landlord',
                'is_active' => true,
            ]
        );
    }
}
