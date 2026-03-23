<?php

namespace App\Console\Commands;

use App\Models\AssignedHoursTemplate;
use Illuminate\Console\Command;

class GenerateAssignedHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assigned-hours:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate assigned hours from templates.';

    public function handle(): int
    {
        $this->info("Generating assigned hours...");
        
        $templates = AssignedHoursTemplate::where('recurrency', '>', 0)
            ->where(function ($query) {
                $query->whereNull('date_end')
                      ->orWhere('date_end', '>=', now());
            })->get();

        foreach ($templates as $template) {
            $template->generateAssignedHours();
        }

        $this->info('Assigned hours generated successfully.');

        return 0;
    }
}
