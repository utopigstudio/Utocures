<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Invoice extends Model implements AuditableContract
{
    use HasFactory, HasUuids, Filterable, Auditable;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'created_at' => 'date:d/m/Y',
        'date' => 'date:d/m/Y',
        'due_date' => 'date:d/m/Y',
    ];

    /**
     * Get the client associated with the invoice.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the lines associated with the invoice.
     */
    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Get the files associated with the invoice.
     */
    public function files()
    {
        return $this->morphMany(File::class, 'filable');
    }

    /**
     * Scope a query to only include invoices of a given year.
     */
    public function scopeYear($query, $year)
    {
        return $query->whereYear('date', $year);
    }
}