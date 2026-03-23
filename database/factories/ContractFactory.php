<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Contract;
use App\Models\File;
use App\Models\Note;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = array_keys(Contract::STATUSES);

        return [
            'title' => fake()->sentence(),
            'client_id' => null,
            'date_start' => fake()->date(),
            'date_end' => fake()->date(),
            'status' => fake()->randomElement($statuses),
            'content' => fake()->randomHtml(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Contract $contract) {
            if (mt_rand(0, 1)) {
                Note::factory()
                    ->count(mt_rand(1, 3))
                    ->for($contract, 'noteable')
                    ->create();
            }

            if (mt_rand(0, 1)) {
                File::factory()
                    ->count(mt_rand(1, 2))
                    ->for($contract, 'fileable')
                    ->create();
            }
        });
    }
}
