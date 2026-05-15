<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ApplySearch
{
    public function applySearch(Builder $query, array $searchData, string $operator = 'LIKE'): Builder
    {
        if (empty($searchData)) return $query;

        $query->where(function ($q) use ($searchData, $operator) {
            foreach ($searchData as $column => $value) {
                if (!is_null($value) && $value !== '') {
                    $finalValue = ($operator === 'LIKE') ? "%{$value}%" : $value;
                    $q->orWhere($column, $operator, $finalValue);
                }
            }
        });

        return $query;
    }
}