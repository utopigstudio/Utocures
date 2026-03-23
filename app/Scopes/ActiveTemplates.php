<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActiveTemplates implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where(function ($q) {
            $q->whereDate('date_end', '>=', today())
              ->orWhereDate('date', '>=', today())
              ->orWhere(function ($q2) {
                  $q2->whereNull('date')
                     ->whereNull('date_end');
              });
        });
    }
}
