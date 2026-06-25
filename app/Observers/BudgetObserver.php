<?php

namespace App\Observers;

use App\Models\Budget;
use App\Models\Client;

class BudgetObserver
{
    /**
     * Set client_name before creating a Budget.
     */
    public function creating(Budget $budget): void
    {
        $client = Client::whereKey($budget->client_id)->first();

        if (!$client) {
            $budget->fill(['client_id' => null, 'client_name' => $budget->client_id]);
        } else {
            $budget->fill(['client_name' => $client->name]);
        }
    }

    /**
     * Update related Client when a Budget is updated.
     */
    public function updating(Budget $budget): void
    {
        $this->creating($budget);
    }
}
