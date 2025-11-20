<?php

namespace Database\Seeders;

use App\Models\Role;
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
        $adminRole = Role::query()->where('name', 'admin')->first();
        $vendorRole = Role::query()->where('name', 'vendor')->first();
        $customerRole = Role::query()->where('name', 'customer')->first();

        $emailExtension = '@thalsecom.com';

        // Create admin user
        User::query()->firstOrCreate(
            ['email' => 'admin' . $emailExtension],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        // Create vendor users
        User::query()->firstOrCreate(
            ['email' => 'vendor1' . $emailExtension],
            [
                'name' => 'Vendor One',
                'password' => Hash::make('password123'),
                'role_id' => $vendorRole->id,
                'is_active' => true,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'vendor2' . $emailExtension],
            [
                'name' => 'Vendor Two',
                'password' => Hash::make('password123'),
                'role_id' => $vendorRole->id,
                'is_active' => true,
            ]
        );

        // Create customer users
        User::query()->firstOrCreate(
            ['email' => 'customer1' . $emailExtension],
            [
                'name' => 'Customer One',
                'password' => Hash::make('password123'),
                'role_id' => $customerRole->id,
                'is_active' => true,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'customer2' . $emailExtension],
            [
                'name' => 'Customer Two',
                'password' => Hash::make('password123'),
                'role_id' => $customerRole->id,
                'is_active' => true,
            ]
        );
    }
}
