<?php

use App\Console\Commands\CheckEmployeeContractExpirations;
use App\Console\Commands\CheckMissedClockIns;
use App\Console\Commands\GenerateAssignedHours;
use App\Console\Commands\GenerateAutomaticInvoices;
use Illuminate\Support\Facades\Schedule;

Schedule::command(GenerateAssignedHours::class)->daily();
Schedule::command(GenerateAutomaticInvoices::class)->monthlyOn(1, '00:00');
Schedule::command(CheckEmployeeContractExpirations::class)
    ->dailyAt('08:00')
    ->withoutOverlapping();
Schedule::command(CheckMissedClockIns::class)
    ->hourlyAt(10)
    ->withoutOverlapping();
