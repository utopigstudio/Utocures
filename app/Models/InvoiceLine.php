<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InvoiceLine extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Get the invoice associated with the invoice line.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}