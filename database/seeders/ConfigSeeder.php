<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BudgetTemplate;
use App\Models\Characteristic;
use App\Models\ContractTemplate;
use App\Models\Service;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add services
        Service::factory()->count(10)->create();

        // Add budget templates
        BudgetTemplate::factory()->count(2)->create();

        // Add contract templates
        ContractTemplate::factory()->count(2)->create();

        // Add characteristics and characteristic options
        Characteristic::factory()->count(5)->hasOptions(3)->create();
    }
}
