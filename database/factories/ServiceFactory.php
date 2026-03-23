<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Services\IconService;
use App\Models\Service;
use App\Models\ServiceTask;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $icons = (new IconService())->listIcons(['healthicons'], '');
        $colors = Service::getColors();

        return [
            'name' => fake()->text(30),
            'icon_slug' => $icons[array_rand($icons)]['slug'],
            'color' => $colors->random()->id,
            'description' => fake()->text(),
            'discount_partner' => fake()->randomFloat(2, 0, 100),
            'price' => fake()->randomFloat(2, 1, 1000)
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Service $service) {
            ServiceTask::factory()
                ->count(5)
                ->create([
                    'name' => fake()->text(30),
                    'service_id' => $service->id
                ]);
        });
    }
}
