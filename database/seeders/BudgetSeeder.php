<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Budget;
use App\Models\BudgetLine;

class BudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Budget::withoutEvents(function () {
            Budget::factory(['client_id' => null])
                ->count(5)
                ->create()
                ->each(function ($budget) {
                    if (rand(0, 1)) {
                        $budget->lines()->saveMany(BudgetLine::factory()->count(mt_rand(1, 3))->make());
                    }
                });
        });
    }
}
