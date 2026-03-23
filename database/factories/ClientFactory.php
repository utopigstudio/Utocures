<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AssignedCharacteristic;
use App\Models\AssignedService;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\CharacteristicOption;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Note;
use App\Models\Service;
use App\Models\Country;
use App\Models\Gender;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countries = Country::all();
        $genders = Gender::all();

        return [
            'name' => fake()->name(),
            'cif_nif' => fake()->dni(),
            'email' => fake()->unique()->safeEmail(),
            'gender_id' => $genders->isNotEmpty() ? fake()->randomElement($genders)->id : null,
            'phone' => fake()->phoneNumber(),
            'phone_2' => fake()->phoneNumber(),
            'birth_date' => fake()->date(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'zip_code' => fake()->postcode(),
            'country_id' => $countries->isNotEmpty() ? fake()->randomElement($countries)->id : null,
            'bank_name' => fake()->company(),
            'bank_account' => fake()->iban('ES'),
            'tax_included' => fake()->boolean(),
            'is_partner' => fake()->boolean(),
            'automatic_invoice' => fake()->boolean(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
        ];
    }

    public function configure()
    {
        $characteristicsOptions = CharacteristicOption::all();
        $services = Service::all();

        return $this->afterCreating(function (Client $client) use ($characteristicsOptions, $services) {
            if (mt_rand(0, 1)) {
                Budget::factory()
                    ->for($client)
                    ->count(mt_rand(1, 2))
                    ->has(BudgetLine::factory()->count(mt_rand(1, 4)), 'lines')
                    ->create();
            }

            if (mt_rand(0, 1)) {
                Invoice::factory()
                    ->for($client)
                    ->count(mt_rand(1, 2))
                    ->has(InvoiceLine::factory()->count(mt_rand(1, 4)), 'lines')
                    ->create();
            }

            if (mt_rand(0, 1)) {
                Contract::factory()
                    ->for($client)
                    ->count(mt_rand(1, 2))
                    ->create();
            }

            if (mt_rand(0, 1)) {
                Note::factory()
                    ->count(mt_rand(1, 3))
                    ->for($client, 'noteable')
                    ->create();
            }

            if (mt_rand(0, 1)) {
                AssignedCharacteristic::factory()
                    ->count(mt_rand(1, 2))
                    ->for($client, 'characterizable')
                    ->for($characteristicsOptions->random())
                    ->create();
            }

            $randomServices = $services->random(mt_rand(1, min(5, $services->count())));

            foreach ($randomServices as $service) {
                AssignedService::factory()
                    ->for($client, 'serviceable')
                    ->for($service)
                    ->create();
            }
        });
    }
}
