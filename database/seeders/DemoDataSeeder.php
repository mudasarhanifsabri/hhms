<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Building;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $landlords = collect([
            ['name' => 'Fakhar Zaman', 'email' => 'landlord@gmail.com', 'phone' => '971501110001'],
            ['name' => 'Sara Malik', 'email' => 'sara.landlord@example.com', 'phone' => '971501110002'],
            ['name' => 'Omar Hassan', 'email' => 'omar.landlord@example.com', 'phone' => '971501110003'],
        ])->map(fn ($user) => User::updateOrCreate(
            ['email' => $user['email']],
            $user + [
                'role' => 'landlord',
                'password' => $password,
                'address' => 'Dubai, UAE',
                'bank_name' => 'Emirates NBD',
                'bank_account_holder' => $user['name'],
                'bank_account_number' => '100200300',
                'bank_account_type' => 'Current Account',
                'swift_code' => 'EBILAEAD',
                'iban' => 'AE070331234567890123456',
                'bank_branch' => 'Dubai Marina',
                'is_active' => true,
            ]
        ));

        collect([
            ['name' => 'Sheeza Shakeel', 'email' => 'agent@gmail.com', 'phone' => '971502220001'],
            ['name' => 'Hamza Ali', 'email' => 'hamza.agent@example.com', 'phone' => '971502220002'],
            ['name' => 'Nadia Khan', 'email' => 'nadia.agent@example.com', 'phone' => '971502220003'],
        ])->each(fn ($user) => User::updateOrCreate(
            ['email' => $user['email']],
            $user + ['role' => 'agent', 'password' => $password, 'agent_commission' => 5, 'is_active' => true]
        ));

        collect([
            ['name' => 'Ahmed Khan', 'email' => 'tenant@gmail.com', 'phone' => '971503330001'],
            ['name' => 'Priya Shah', 'email' => 'priya.tenant@example.com', 'phone' => '971503330002'],
            ['name' => 'James Wilson', 'email' => 'james.tenant@example.com', 'phone' => '971503330003'],
        ])->each(fn ($user) => User::updateOrCreate(
            ['email' => $user['email']],
            $user + ['role' => 'tenant', 'password' => $password, 'address' => 'Dubai, UAE', 'is_active' => true]
        ));

        collect([
            ['name' => 'Ayyan Khan', 'email' => 'maintainer@gmail.com', 'phone' => '971504440001'],
            ['name' => 'Bilal Ahmed', 'email' => 'bilal.maintainer@example.com', 'phone' => '971504440002'],
        ])->each(fn ($user) => User::updateOrCreate(
            ['email' => $user['email']],
            $user + ['role' => 'maintainer', 'password' => $password, 'address' => 'Dubai, UAE', 'is_active' => true]
        ));

        $buildings = collect([
            ['building_name' => 'Marina Pearl Tower', 'address' => 'Dubai Marina', 'city' => 'Dubai', 'country' => 'UAE', 'year_built' => 2018],
            ['building_name' => 'Downtown Vista', 'address' => 'Downtown Dubai', 'city' => 'Dubai', 'country' => 'UAE', 'year_built' => 2020],
            ['building_name' => 'Palm Residence', 'address' => 'Palm Jumeirah', 'city' => 'Dubai', 'country' => 'UAE', 'year_built' => 2019],
        ])->map(fn ($building) => Building::updateOrCreate(
            ['building_name' => $building['building_name']],
            $building + [
                'management_email' => 'management@example.com',
                'security_contact' => '971505550000',
                'gas_provider' => 'Emirates Gas',
            ]
        ));

        collect(['WiFi', 'Pool', 'Gym', 'Parking', 'Security', 'Sea View', 'Balcony', 'Smart Lock'])
            ->each(fn ($name) => Amenity::updateOrCreate(['name' => $name]));

        $properties = [
            ['name' => 'Marina Studio 1204', 'category' => 'Apartment', 'rent' => 4500, 'status' => 'rented', 'bedrooms' => 1, 'bathrooms' => 1, 'building' => 0, 'landlord' => 0, 'expiry' => 12],
            ['name' => 'Downtown One Bedroom', 'category' => 'Apartment', 'rent' => 6800, 'status' => 'vacant', 'bedrooms' => 1, 'bathrooms' => 2, 'building' => 1, 'landlord' => 1, 'expiry' => 45],
            ['name' => 'Palm Sea View Suite', 'category' => 'Residence', 'rent' => 12000, 'status' => 'rented', 'bedrooms' => 2, 'bathrooms' => 3, 'building' => 2, 'landlord' => 2, 'expiry' => 25],
            ['name' => 'Marina Family Apartment', 'category' => 'Apartment', 'rent' => 9300, 'status' => 'vacant', 'bedrooms' => 2, 'bathrooms' => 2, 'building' => 0, 'landlord' => 0, 'expiry' => 75],
            ['name' => 'Downtown Premium Penthouse', 'category' => 'Penthouse', 'rent' => 22000, 'status' => 'rented', 'bedrooms' => 3, 'bathrooms' => 4, 'building' => 1, 'landlord' => 1, 'expiry' => 7],
            ['name' => 'Palm Garden Villa', 'category' => 'Villas', 'rent' => 18000, 'status' => 'vacant', 'bedrooms' => 4, 'bathrooms' => 5, 'building' => 2, 'landlord' => 2, 'expiry' => 120],
        ];

        $createdProperties = collect();

        foreach ($properties as $property) {
            $createdProperties->push(Property::updateOrCreate(
                ['name' => $property['name']],
                [
                    'landlord_id' => $landlords[$property['landlord']]->id,
                    'building_id' => $buildings[$property['building']]->id,
                    'category' => $property['category'],
                    'rent' => $property['rent'],
                    'management_fee' => round($property['rent'] * 0.12, 2),
                    'status' => $property['status'],
                    'bedrooms' => $property['bedrooms'],
                    'bathrooms' => $property['bathrooms'],
                    'living_rooms' => 1,
                    'kitchens' => 1,
                    'square_foot' => 850 + ($property['bedrooms'] * 300),
                    'floor' => rand(3, 28),
                    'description' => 'Demo holiday home record for testing dashboard and admin pages.',
                    'amenities' => ['WiFi', 'Parking', 'Security'],
                    'has_security' => true,
                    'security_utilities' => ['CCTV', 'Concierge'],
                    'additional_features' => ['Balcony'],
                    'dtcm_permit_no' => 'DTCM-'.rand(10000, 99999),
                    'dtcm_permit_expiry' => Carbon::today()->addDays($property['expiry']),
                    'wifi_provider' => 'du',
                    'electricity_provider' => 'DEWA',
                ]
            ));
        }

        $createdProperties->where('status', 'rented')->each(function (Property $property) {
            $rent = (float) $property->rent;
            $entries = [
                ['type' => 'rent_income', 'amount' => $rent, 'entry_date' => Carbon::today()->startOfMonth(), 'reference' => 'RENT-' . now()->format('Ym'), 'description' => 'Monthly rent collected'],
                ['type' => 'management_fee', 'amount' => (float) $property->management_fee, 'entry_date' => Carbon::today()->startOfMonth()->addDay(), 'reference' => 'MGMT-' . now()->format('Ym'), 'description' => 'Management fee deduction'],
                ['type' => 'dewa', 'amount' => round($rent * 0.035, 2), 'entry_date' => Carbon::today()->startOfMonth()->addDays(2), 'reference' => 'DEWA-' . now()->format('Ym'), 'description' => 'DEWA bill deduction'],
                ['type' => 'gas', 'amount' => round($rent * 0.012, 2), 'entry_date' => Carbon::today()->startOfMonth()->addDays(3), 'reference' => 'GAS-' . now()->format('Ym'), 'description' => 'Gas bill deduction'],
                ['type' => 'maintenance', 'amount' => round($rent * 0.025, 2), 'entry_date' => Carbon::today()->startOfMonth()->addDays(4), 'reference' => 'MNT-' . now()->format('Ym'), 'description' => 'Routine maintenance charge'],
                ['type' => 'payout', 'amount' => round($rent - (float) $property->management_fee - ($rent * 0.035) - ($rent * 0.012) - ($rent * 0.025), 2), 'entry_date' => Carbon::today()->startOfMonth()->addDays(5), 'reference' => 'TRF-' . now()->format('Ym'), 'description' => 'Owner payout transfer'],
            ];

            foreach ($entries as $entry) {
                LandlordAccountEntry::updateOrCreate(
                    [
                        'landlord_id' => $property->landlord_id,
                        'property_id' => $property->id,
                        'type' => $entry['type'],
                        'reference' => $entry['reference'],
                    ],
                    $entry + [
                        'landlord_id' => $property->landlord_id,
                        'property_id' => $property->id,
                        'direction' => LandlordAccountEntry::directionForType($entry['type']),
                    ]
                );
            }

            LandlordAccountEntry::recalculateBalancesFor($property->landlord_id);
        });
    }
}
