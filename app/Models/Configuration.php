<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Configuration extends Model implements AuditableContract
{
    use HasFactory, HasUuids, Auditable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_name',
        'company_cif_nif',
        'company_email',
        'company_phone',
        'company_address',
        'company_city',
        'company_zip_code',
        'company_country_id',
        'company_image',
    ];

    /**
     * Accessor for company_image attribute to return full URL.
     */
    public function getCompanyImageAttribute($value)
    {
        return $value ? Storage::url($value) : null;
    }
}
