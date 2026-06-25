<?php

namespace App\Providers;

use App\Models\AssignedHoursTemplate;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Note;
use App\Models\User;
use App\Observers\AssignedHoursTemplateObserver;
use App\Observers\BudgetLineObserver;
use App\Observers\BudgetObserver;
use App\Observers\ClientObserver;
use App\Observers\EmployeeObserver;
use App\Observers\InvoiceLineObserver;
use App\Observers\InvoiceObserver;
use App\Observers\NoteObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Invoice::observe(InvoiceObserver::class);
        InvoiceLine::observe(InvoiceLineObserver::class);
        BudgetLine::observe(BudgetLineObserver::class);
        Budget::observe(BudgetObserver::class);
        Client::observe(ClientObserver::class);
        AssignedHoursTemplate::observe(AssignedHoursTemplateObserver::class);
        User::observe(UserObserver::class);
        Employee::observe(EmployeeObserver::class);
        Note::observe(NoteObserver::class);

        app()->useLangPath(base_path('lang'));
    }
}
