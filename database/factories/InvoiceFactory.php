<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Invoice;
use App\Models\File;
use App\Models\Country;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countries = Country::all();

        return [
            'client_id' => null,
            'date' => fake()->date(),
            'due_date' => fake()->date(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'zip_code' => fake()->postcode(),
            'country_id' => $countries ? fake()->randomElement($countries)->id : null,
            'subtotal' => fake()->randomFloat(2, 10, 1000),
            'discount' => fake()->randomFloat(2, 0, 100),
            'tax' => fake()->randomFloat(2, 0, 100),
            'total' => fake()->randomFloat(2, 10, 1000),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Invoice $invoice) {
            if (mt_rand(0, 1)) {
                File::factory()
                    ->count(mt_rand(1, 2))
                    ->for($invoice, 'fileable')
                    ->create();
            }
        });
    }
}
