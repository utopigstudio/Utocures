<?php

namespace App\Observers;

use App\Models\Client;

class ClientObserver
{
    /**
     * Update related Budgets when a Client is updated.
     */
    public function updated(Client $client): void
    {
        if ($client->wasChanged('name')) {
            $budgets = $client->budgets;
            if (!$budgets) return;
            
            foreach ($budgets as $b) {
                $b->update([
                    'client_name' => $client->name,
                ]);
            }
        }
    }
}
