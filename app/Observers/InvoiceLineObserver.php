<?php

namespace App\Observers;

use App\Models\InvoiceLine;

class InvoiceLineObserver
{
    /**
     * Calculate fields before creating or updating a InvoiceLine.
     */
    public function creating(InvoiceLine $line): void
    {
        $line->subtotal = $line->price * $line->quantity;
        $line->tax = ($line->subtotal - ($line->discount ?? 0)) * ($line->tax_type / 100);
    }

    /**
     * Recalculate Invoice totals after a InvoiceLine is created.
     */
    public function saved(InvoiceLine $line): void
    {
        $invoice = $line->invoice;
        if (!$invoice) return;

        $invoice->fill([
            'subtotal' => $invoice->lines()->sum('subtotal'),
            'discount' => $invoice->lines()->sum('discount'),
            'tax' => $invoice->lines()->sum('tax'),
            'total' => $invoice->lines()->sum('subtotal') - $invoice->lines()->sum('discount') + $invoice->lines()->sum('tax'),
        ])->saveQuietly();
    }
}
