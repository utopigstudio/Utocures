<?php

namespace App\Console\Commands;

use App\Models\AdminNotification;
use App\Models\Employee;
use Illuminate\Console\Command;

class CheckEmployeeContractExpirations extends Command
{
    public const NOTICE_DAYS = [30, 7];

    protected $signature = 'notifications:check-employee-contract-expirations';

    protected $description = 'Create admin notifications for employee contracts ending in 30 or 7 days.';

    public function handle(): int
    {
        $created = 0;

        foreach (self::NOTICE_DAYS as $noticeDays) {
            $contractEndDate = today()->addDays($noticeDays)->toDateString();

            Employee::query()
                ->select(['id', 'user_id', 'contract_end_date'])
                ->with('user:id,name,is_active')
                ->whereDate('contract_end_date', $contractEndDate)
                ->whereHas('user', fn ($query) => $query->where('is_active', true))
                ->chunkById(200, function ($employees) use ($noticeDays, $contractEndDate, &$created) {
                    foreach ($employees as $employee) {
                        $alreadyCreated = AdminNotification::query()
                            ->where('type', 'employee_contract_expiring')
                            ->where('notifiable_type', $employee->getMorphClass())
                            ->where('notifiable_id', $employee->id)
                            ->where('data->notice_days', $noticeDays)
                            ->where('data->contract_end_date', $contractEndDate)
                            ->exists();

                        if ($alreadyCreated) {
                            continue;
                        }

                        AdminNotification::query()->create([
                            'type' => 'employee_contract_expiring',
                            'title' => __('notifications.employee_contract_expiring_title'),
                            'message' => __('notifications.employee_contract_expiring_message', [
                                'employee' => $employee->user->name,
                                'date' => $employee->contract_end_date->format('d/m/Y'),
                                'days' => $noticeDays,
                            ]),
                            'action_url' => route('employees.show', $employee),
                            'notifiable_type' => $employee->getMorphClass(),
                            'notifiable_id' => $employee->id,
                            'data' => [
                                'employee_id' => $employee->id,
                                'employee_name' => $employee->user->name,
                                'contract_end_date' => $contractEndDate,
                                'notice_days' => $noticeDays,
                            ],
                        ]);

                        $created++;
                    }
                });
        }

        $this->info("Contract expiration notifications created: {$created}");

        return self::SUCCESS;
    }
}
