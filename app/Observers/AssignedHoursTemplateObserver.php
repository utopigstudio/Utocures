<?php

namespace App\Observers;

use App\Models\AssignedHoursTemplate;

class AssignedHoursTemplateObserver
{
    /**
     * Handle the AssignedHoursTemplate "created" event.
     */
    public function created(AssignedHoursTemplate $assignedHoursTemplate): void
    {
        $assignedHoursTemplate->generateAssignedHours();
    }

    /**
     * Handle the AssignedHoursTemplate "updated" event.
     */
    public function updated(AssignedHoursTemplate $assignedHoursTemplate): void
    {
        $fieldsChanged = $assignedHoursTemplate->getChanges();
        unset($fieldsChanged['updated_at']);

        // if original date start was today or after, just regenerate assigned hours
        $originalDateStart = $assignedHoursTemplate->getOriginal('date_start');
        if (!$originalDateStart) {
            $firstAssignedHour = $assignedHoursTemplate->assignedHours()->orderBy('date', 'asc')->first();
            if ($firstAssignedHour) {
                $originalDateStart = $firstAssignedHour->date;
            }
        }
        if (!$originalDateStart || !$originalDateStart->lessThan(today())) {
            $assignedHoursTemplate->assignedHours()->delete();
            $assignedHoursTemplate->generateAssignedHours();

            return;
        }

        // if only date_end changed (and no other fields)
        if (count($fieldsChanged) == 1 && (array_key_exists('date_end', $fieldsChanged))) {
            // if date_end changed and is less than before, delete assigned hours beyond the new date_end
            $originalDateEnd = $assignedHoursTemplate->getOriginal('date_end');
            $newDateEnd = $assignedHoursTemplate->date_end;
            if (!$originalDateEnd || ($originalDateEnd && $newDateEnd && $newDateEnd->lessThan($originalDateEnd))) {
                $assignedHoursTemplate->assignedHours()->where('date', '>', $newDateEnd)->delete();
            }

            // generate missing assigned hours if needed
            $assignedHoursTemplate->generateAssignedHours();

            return;
        }

        // create new template with new data and date_start as today
        $newTemplate = $assignedHoursTemplate->replicate(['id', 'created_at', 'updated_at']);
        $newTemplate->date_start = today();

        // reset all changes from existing template
        $assignedHoursTemplate->syncOriginal();
        // set existing template date_end to yesterday
        $yesterday = today()->subDay();
        $assignedHoursTemplate->date_end = $yesterday;
        $assignedHoursTemplate->save(); // this will trigger deletion of assigned hours beyond yesterday

        // save new template
        $newTemplate->save(); // this will trigger generation of assigned hours from today onwards
    }
}
