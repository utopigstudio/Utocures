<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

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
            'password' => bcrypt('test123')
        ]);
        
        User::factory()->create([
            'name' => 'Test Employee',
            'email' => 'employee@test.es',
            'password' => bcrypt('test123')
        ]);

        User::factory()->count(10)->create();
    }
}
