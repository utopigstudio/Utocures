<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Budget;
use App\Models\File;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = array_keys(Budget::STATUSES);

        return [
            'client_id' => null,
            'client_name' => fake()->name(),
            'content' => fake()->randomHtml(),
            'due_date' => fake()->date(),
            'status' => fake()->randomElement($statuses),
            'subtotal' => fake()->randomFloat(2, 10, 1000),
            'discount' => fake()->randomFloat(2, 0, 100),
            'tax' => fake()->randomFloat(2, 0, 100),
            'total' => fake()->randomFloat(2, 10, 1000),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Budget $budget) {
            if (mt_rand(0, 1)) {
                File::factory()
                    ->count(mt_rand(1, 2))
                    ->for($budget, 'fileable')
                    ->create();
            }
        });
    }
}
