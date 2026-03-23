<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    public function scopeFilter(Builder $query, array $filters, array $searchable): Builder
    {
        return $query
            ->when(!empty($filters['filter_search']) && !empty($searchable), function ($q) use ($filters, $searchable) {
                $search = $filters['filter_search'];
                $q->where(function ($qq) use ($search, $searchable) {
                    foreach ($searchable as $field) {
                        if (str_contains($field, '.')) {
                            [$relation, $column] = explode('.', $field, 2);
                            $qq->orWhereHas($relation, function ($sub) use ($column, $search) {
                                $sub->where($column, 'like', "%{$search}%");
                            });
                        } else {
                            $qq->orWhere($field, 'like', "%{$search}%");
                        }
                    }
                });
            })
            ->when(array_key_exists('filter_is_partner', $filters) && $filters['filter_is_partner'] !== null, function ($q) use ($filters) {
                $q->where('is_partner', (int) $filters['filter_is_partner']);
            })
            ->when(array_key_exists('filter_status', $filters) && $filters['filter_status'] !== null, function ($q) use ($filters) {
                $q->where('status', $filters['filter_status']);
            })
            ->when(!empty($filters['filter_color']), function ($q) use ($filters) {
                $q->where('color', $filters['filter_color']);
            })
            ->when(array_key_exists('filter_is_active', $filters) && $filters['filter_is_active'] !== null, function ($q) use ($filters) {
                $q->where('is_active', (int) $filters['filter_is_active']);
            });
    }
}
