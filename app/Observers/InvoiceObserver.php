<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\Invoice;

class InvoiceObserver
{
    /**
     * Handle the Invoice "creating" event.
     */
    public function creating(Invoice $invoice): void
    {
        $recipient = Client::findOrFail($invoice->client_id)->billingRecipientData();
        $invoice->billing_recipient = $recipient;
        $invoice->address = $recipient['address'];
        $invoice->city = $recipient['city'];
        $invoice->zip_code = $recipient['zip_code'];
        $invoice->country_id = $recipient['country_id'];
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
