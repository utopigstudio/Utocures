<?php

namespace App\Exports;

use App\Models\Client;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeTimeRecordExport implements WithMultipleSheets
{
    private const EMPLOYEES_WITH_CLIENTS_FIELDS = [
        'employee',
        'client',
        'service_name',
        'programmed_hours',
        'registered_hours',
    ];

    private const CLIENTS_WITH_EMPLOYEES_FIELDS = [
        'client',
        'employee',
        'service_name',
        'programmed_hours',
        'registered_hours',
    ];

    private const EMPLOYEES_SIMPLE_FIELDS = [
        'employee',
        'programmed_hours',
        'registered_hours',
    ];

    public function __construct(
        protected Collection $employees,
        protected Collection $clients,
        protected array $preRows = [],
    ) {}

    public function sheets(): array
    {
        return [
            new BaseExport(
                $this->employeesWithClientsRows(),
                'employee_time_records',
                self::EMPLOYEES_WITH_CLIENTS_FIELDS,
                $this->preRows,
                __('employee_time_records.sheet_employees_clients'),
            ),
            new BaseExport(
                $this->clientsWithEmployeesRows(),
                'employee_time_records',
                self::CLIENTS_WITH_EMPLOYEES_FIELDS,
                $this->preRows,
                __('employee_time_records.sheet_clients_employees'),
            ),
            new BaseExport(
                $this->employeesSimpleRows(),
                'employee_time_records',
                self::EMPLOYEES_SIMPLE_FIELDS,
                $this->preRows,
                __('employee_time_records.sheet_employees_simple'),
            ),
        ];
    }

    private function employeesWithClientsRows(): Collection
    {
        return $this->employees
            ->flatMap(function (Employee $employee): Collection {
                $summaryRow = collect([[
                    'employee' => $employee->user?->name,
                    'client' => null,
                    'service_name' => null,
                    'programmed_hours' => $employee->programmed_hours,
                    'registered_hours' => $employee->registered_hours,
                ]]);

                $detailRows = collect($employee->grouped_details ?? [])
                    ->map(fn (array $detail) => [
                        'employee' => null,
                        'client' => $detail['client_name'] ?? null,
                        'service_name' => $detail['service_name'] ?? null,
                        'programmed_hours' => $detail['programmed_hours'] ?? null,
                        'registered_hours' => $detail['registered_hours'] ?? null,
                    ]);

                return $summaryRow->merge($detailRows);
            })
            ->values();
    }

    private function clientsWithEmployeesRows(): Collection
    {
        return $this->clients
            ->flatMap(function (Client $client): Collection {
                $summaryRow = collect([[
                    'client' => $client->name,
                    'employee' => null,
                    'service_name' => null,
                    'programmed_hours' => $client->programmed_hours,
                    'registered_hours' => $client->registered_hours,
                ]]);

                $detailRows = collect($client->grouped_details ?? [])
                    ->map(fn (array $detail) => [
                        'client' => null,
                        'employee' => $detail['employee_name'] ?? null,
                        'service_name' => $detail['service_name'] ?? null,
                        'programmed_hours' => $detail['programmed_hours'] ?? null,
                        'registered_hours' => $detail['registered_hours'] ?? null,
                    ]);

                return $summaryRow->merge($detailRows);
            })
            ->values();
    }

    private function employeesSimpleRows(): Collection
    {
        return $this->employees
            ->map(fn (Employee $employee) => [
                'employee' => $employee->user?->name,
                'programmed_hours' => $employee->programmed_hours,
                'registered_hours' => $employee->registered_hours,
            ])
            ->values();
    }
}
