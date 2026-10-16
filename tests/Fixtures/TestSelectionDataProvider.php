<?php

namespace Tests\Fixtures;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Callable data sources for the selection tests, returning the rows in each shape a callable may use.
 */
class TestSelectionDataProvider
{
    public const ROWS = [
        ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'electronics', 'price' => 1299.99],
        ['id' => 2, 'name' => 'Wireless Mouse', 'category' => 'electronics', 'price' => 29.99],
        ['id' => 3, 'name' => 'Office Desk', 'category' => 'furniture', 'price' => 349.00],
        ['id' => 4, 'name' => 'Ergonomic Chair', 'category' => 'furniture', 'price' => 599.00],
        ['id' => 5, 'name' => 'USB Cable', 'category' => 'electronics', 'price' => 9.99],
        ['id' => 6, 'name' => 'Bookshelf', 'category' => 'furniture', 'price' => 129.00],
    ];

    public function builder(LwDataRetrievalParams $params): EloquentBuilder
    {
        return $params->apply(TestProduct::query());
    }

    public function paginatedBuilder(LwDataRetrievalParams $params): LengthAwarePaginator
    {
        return $params->applyAndPaginate(TestProduct::query());
    }

    public function collection(LwDataRetrievalParams $params): Collection
    {
        return $params->apply(collect(static::ROWS));
    }

    public function paginatedCollection(LwDataRetrievalParams $params): LengthAwarePaginator
    {
        return $params->applyAndPaginate(collect(static::ROWS));
    }
}
