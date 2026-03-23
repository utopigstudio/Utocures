<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\Filterable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class BudgetTemplate extends Model implements AuditableContract
{
    use HasFactory, HasUuids, Filterable, Auditable;

    protected $guarded = ['id', 'created_at', 'updated_at'];
}