<?php

namespace App\Observers;

use App\Models\BudgetLine;

class BudgetLineObserver
{
    /**
     * Calculate fields before creating or updating a BudgetLine.
     */
    public function creating(BudgetLine $line): void
    {
        $line->subtotal = $line->price * $line->quantity;
        $line->tax = ($line->subtotal - ($line->discount ?? 0)) * ($line->tax_type / 100);
    }

    /**
     * Recalculate Budget totals after a BudgetLine is created.
     */
    public function saved(BudgetLine $line): void
    {
        $budget = $line->budget;
        if (!$budget) return;

        $budget->fill([
            'subtotal' => $budget->lines()->sum('subtotal'),
            'discount' => $budget->lines()->sum('discount'),
            'tax' => $budget->lines()->sum('tax'),
            'total' => $budget->lines()->sum('subtotal') - $budget->lines()->sum('discount') + $budget->lines()->sum('tax'),
        ])->saveQuietly();
    }
}
