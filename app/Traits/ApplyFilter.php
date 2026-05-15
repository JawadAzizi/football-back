<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ApplyFilter
{
    public function applyFilter(Builder $query, array $filterData, string $operator = '='): Builder
    {
        foreach ($filterData as $column => $value) {
            if (!is_null($value) && $value !== '') {
                $query->where($column, $operator, $value);
            }
        }
        return $query;
    }
}