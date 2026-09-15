<?php

namespace Database\Seeders;

use App\Models\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@test.es',
            'password' => bcrypt('test123'),
        ]);

        User::factory()->create([
            'name' => 'Test Employee',
            'email' => 'employee@test.es',
            'role' => UserRole::EMPLOYEE,
            'password' => bcrypt('test123'),
        ]);

        User::factory()->create([
            'name' => 'Test Client',
            'email' => 'client@test.es',
            'role' => UserRole::CLIENT,
            'password' => bcrypt('test123'),
        ]);
    }
}
