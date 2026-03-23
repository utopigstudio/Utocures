<?php

use App\Console\Commands\GenerateAssignedHours;
use App\Console\Commands\GenerateAutomaticInvoices;
use Illuminate\Support\Facades\Schedule;

Schedule::command(GenerateAssignedHours::class)->daily();
Schedule::command(GenerateAutomaticInvoices::class)->monthlyOn(1, '00:00');