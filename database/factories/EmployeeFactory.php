<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AssignedCharacteristic;
use App\Models\AssignedHoursTemplate;
use App\Models\AssignedService;
use App\Models\CharacteristicOption;
use App\Models\Employee;
use App\Models\Client;
use App\Models\File;
use App\Models\Note;
use App\Models\Service;
use App\Models\User;
use App\Models\Gender;
use App\Models\Country;
use App\Models\AvailableHour;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $users = User::whereNotIn('email', ['test@test.es', 'employee@test.es'])->get();
        $genders = Gender::all();
        $countries = Country::all();

        return [
            'user_id' => $users->isNotEmpty() ? fake()->randomElement($users)->id : null,
            'nif' => fake()->dni(),
            'gender_id' => $genders->isNotEmpty() ? fake()->randomElement($genders)->id : null,
            'birth_date' => fake()->date(),
            'hire_date' => fake()->date(),
            'phone' => fake()->phoneNumber(),
            'phone_2' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'zip_code' => fake()->postcode(),
            'country_id' => $countries->isNotEmpty() ? fake()->randomElement($countries)->id : null,
        ];
    }

    public function configure()
    {
        $characteristicsOptions = CharacteristicOption::all();
        $services = Service::all();
        $clients = Client::all();

        return $this->afterCreating(function (Employee $employee) use ($characteristicsOptions, $services, $clients) {
            if (mt_rand(0, 1)) {
                Note::factory()
                    ->count(mt_rand(1, 3))
                    ->for($employee, 'noteable')
                    ->create();
            }

            if (mt_rand(0, 1)) {
                AssignedCharacteristic::factory()
                    ->count(mt_rand(1, 2))
                    ->for($employee, 'characterizable')
                    ->for($characteristicsOptions->random())
                    ->create();
            }

            if (mt_rand(0, 1)) {
                $randomServices = $services->random(mt_rand(1, min(5, $services->count())));

                foreach ($randomServices as $service) {
                    AssignedService::factory()
                        ->for($employee, 'serviceable')
                        ->for($service)
                        ->create();
                }
            }

            if (mt_rand(0, 1)) {
                File::factory()
                    ->count(mt_rand(1, 2))
                    ->for($employee, 'fileable')
                    ->create();
            }

            $client = $clients->random();
            AssignedHoursTemplate::factory()
                ->for($employee)
                ->for($client)
                ->for($client->services->random())
                ->create();

            for ($i = 0; $i < 7; $i++) {
                AvailableHour::factory()
                    ->for($employee, 'hourable')
                    ->create(['day_of_week' => $i]);
            }
        });
    }
}
