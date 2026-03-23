<?php

namespace App\Observers;

use App\Models\Employee;
use App\Notifications\EmployeeCreatedNotification;
use Illuminate\Support\Facades\Password;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        $token = Password::broker()->createToken($employee->user);
        $employee->user->notify(new EmployeeCreatedNotification($employee->user->name, $token));
    }
}
