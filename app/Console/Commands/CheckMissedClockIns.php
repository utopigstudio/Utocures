<?php

namespace App\Console\Commands;

use App\Models\AdminNotification;
use App\Models\AssignedHour;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CheckMissedClockIns extends Command
{
    public const GRACE_MINUTES = 10;

    protected $signature = 'notifications:check-missed-clock-ins';

    protected $description = 'Create admin notifications when an employee has not clocked in for a scheduled shift.';

    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $cutoff = $now->subMinutes(self::GRACE_MINUTES);

        if (! $cutoff->isSameDay($now)) {
            $this->info('Missed clock-in notifications created: 0');

            return self::SUCCESS;
        }

        $created = 0;

        AssignedHour::query()
            ->whereDate('date', $now->toDateString())
            ->whereTime('time_start', '<=', $cutoff->format('H:i:s'))
            ->whereDoesntHave('timeRecords')
            ->whereHas('employee.user', fn ($query) => $query->where('is_active', true))
            ->with([
                'client:id,name',
                'service:id,name',
                'employee.user:id,name,is_active',
                'employee.statusPeriods' => fn ($query) => $query
                    ->where('start_at', '<=', $now->endOfDay())
                    ->where('end_at', '>=', $now->startOfDay()),
            ])
            ->chunkById(200, function ($assignedHours) use (&$created) {
                foreach ($assignedHours as $assignedHour) {
                    $scheduledStart = CarbonImmutable::parse(
                        $assignedHour->date->toDateString().' '.$assignedHour->time_start->format('H:i:s')
                    );

                    $employeeIsExcused = $assignedHour->employee->statusPeriods->contains(
                        fn ($period) => $period->start_at->lessThanOrEqualTo($scheduledStart)
                            && $period->end_at->greaterThanOrEqualTo($scheduledStart)
                    );

                    if ($employeeIsExcused) {
                        continue;
                    }

                    $notification = AdminNotification::query()->firstOrCreate(
                        [
                            'type' => 'employee_missed_clock_in',
                            'notifiable_type' => $assignedHour->getMorphClass(),
                            'notifiable_id' => $assignedHour->id,
                        ],
                        [
                            'title' => __('notifications.employee_missed_clock_in_title'),
                            'message' => __('notifications.employee_missed_clock_in_message', [
                                'employee' => $assignedHour->employee->user->name,
                                'time' => $scheduledStart->format('H:i'),
                                'date' => $scheduledStart->format('d/m/Y'),
                                'client' => $assignedHour->client->name,
                            ]),
                            'action_url' => route('employee-time-records.index', [
                                'view' => 'calendar',
                                'employee_id' => $assignedHour->employee_id,
                                'filter_date' => $scheduledStart->toDateString(),
                            ]),
                            'data' => [
                                'assigned_hour_id' => $assignedHour->id,
                                'employee_id' => $assignedHour->employee_id,
                                'employee_name' => $assignedHour->employee->user->name,
                                'client_id' => $assignedHour->client_id,
                                'client_name' => $assignedHour->client->name,
                                'service_name' => $assignedHour->service->name,
                                'scheduled_start' => $scheduledStart->toIso8601String(),
                            ],
                        ]
                    );

                    if ($notification->wasRecentlyCreated) {
                        $created++;
                    }
                }
            });

        $this->info("Missed clock-in notifications created: {$created}");

        return self::SUCCESS;
    }
}
