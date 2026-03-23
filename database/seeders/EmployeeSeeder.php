<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::factory()->count(30)->create();

        $user_employee = User::where('email', 'employee@test.es')->first();
        Employee::factory()->count(1)->create(['user_id' => $user_employee->id]);
    }
}
