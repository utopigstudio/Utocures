<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AssignedHoursTemplateFactory extends Factory
{
    public function definition(): array
    {
        $timeStart = sprintf('%02d:%02d:00', fake()->numberBetween(8, 17), fake()->numberBetween(0, 59));
        $timeEndTimestamp = strtotime($timeStart . ' +' . fake()->numberBetween(30, 180) . ' minutes');
        $maxEndTimestamp = strtotime('18:00:00');

        if ($timeEndTimestamp > $maxEndTimestamp) {
            $timeEndTimestamp = $maxEndTimestamp;
        }

        $date = null;
        $daysOfWeek = [];
        $dateStart = null;
        $recurrency = fake()->randomElement([0, 1, 2]);
        if ($recurrency == 0) {
            $date = fake()->dateTimeBetween('now', '+6 months');
        } else {
            $daysOfWeek = fake()->randomElements(['0', '1', '2', '3', '4', '5', '6'], fake()->numberBetween(1, 7), false);
            sort($daysOfWeek);
            $dateStart = fake()->dateTimeBetween('now', '+1 month');
        }

        return [
            'date' => $date?->format('Y-m-d'),
            'days_of_week' => $daysOfWeek,
            'recurrency' => $recurrency,
            'date_start' => $dateStart?->format('Y-m-d'),
            'time_start' => $timeStart,
            'time_end' => date('H:i:s', $timeEndTimestamp),
            'date_end' => fake()->optional()->dateTimeBetween('+1 week', '+6 months')?->format('Y-m-d')
        ];
    }
}
