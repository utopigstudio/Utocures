<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceLine;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Invoice::factory(['client_id' => null])
            ->create()
            ->each(function ($invoice) {
                if (rand(0, 1)) {
                    $invoice->lines()->saveMany(InvoiceLine::factory()->count(mt_rand(1, 3))->make());
                }
            });
    }
}
