<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CommonQueryScopes
{
    /**
     * Scope a query to filter by a price range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $minPrice
     * @param  float  $maxPrice
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByPrice(Builder $query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    /**
     * Scope a query to search by product name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByName(Builder $query, $name)
    {
        return $query->where('name', 'like', '%' . $name . '%');
    }
}
