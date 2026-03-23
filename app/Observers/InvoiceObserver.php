<?php

namespace App\Observers;

use App\Models\Invoice;

class InvoiceObserver
{
    /**
     * Handle the Invoice "creating" event.
     */
    public function creating(Invoice $invoice): void
    {
        $invoice->address = $invoice->client->address;
        $invoice->city = $invoice->client->city;
        $invoice->zip_code = $invoice->client->zip_code;
        $invoice->country_id = $invoice->client->country_id;
    }

    /**
     * Handle the Invoice "updating" event.
     */
    public function updating(Invoice $invoice): void
    {
        if ($invoice->isDirty('client_id')) {
            $this->creating($invoice);
        }
    }
}
