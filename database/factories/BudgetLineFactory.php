<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetLine>
 */
class BudgetLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_id' => null,
            'concept' => fake()->text(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'quantity' => fake()->randomNumber(2),
            'discount' => fake()->randomFloat(2, 0, 100),
            'tax_type' => fake()->randomElement(['21', '10', '4', '0']),
            'tax' => fake()->randomFloat(2, 0, 100),
            'subtotal' => fake()->randomFloat(2, 10, 1000),
        ];
    }
}
