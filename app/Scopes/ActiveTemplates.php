<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActiveTemplates implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $today = today()->toDateString();

        $builder->where(function ($q) use ($today) {
            $q->where('date_end', '>=', $today)
                ->orWhere('date', '>=', $today)
                ->orWhere(function ($q2) {
                    $q2->whereNull('date')
                        ->whereNull('date_end');
                });
        });
    }
}
