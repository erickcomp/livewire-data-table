<?php

use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Data\IterableDataSource;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Support\Collection;
use Illuminate\View\ComponentAttributeBag;

function iterableProducts(): array
{
    return [
        ['name' => 'Laptop Pro', 'category' => 'electronics', 'price' => 1299.99],
        ['name' => 'Wireless Mouse', 'category' => 'electronics', 'price' => 29.99],
        ['name' => 'Office Desk', 'category' => 'furniture', 'price' => 349.00],
        ['name' => 'Ergonomic Chair', 'category' => 'furniture', 'price' => 599.00],
        ['name' => 'USB Cable', 'category' => 'accessories', 'price' => 9.99],
        ['name' => 'Laptop Stand', 'category' => 'accessories', 'price' => 49.99],
    ];
}

function makeIterableParams(array $overrides = []): LwDataRetrievalParams
{
    $dataTable = $overrides['dataTable'] ?? new DataTable();

    return new LwDataRetrievalParams(
        page: $overrides['page'] ?? 1,
        perPage: $overrides['perPage'] ?? '15',
        pageName: $overrides['pageName'] ?? 'page',
        search: $overrides['search'] ?? null,
        columnsSearch: $overrides['columnsSearch'] ?? [],
        filters: $overrides['filters'] ?? [],
        sortBy: $overrides['sortBy'] ?? '',
        sortDir: $overrides['sortDir'] ?? '',
        collectionsSortingFlags: $overrides['collectionsSortingFlags'] ?? SORT_NATURAL | SORT_FLAG_CASE,
        dataTable: $dataTable,
    );
}

// --- Range filter ---

it('filters with range mode using both from and to', function () {
    $source = new IterableDataSource(iterableProducts(), DataSourcePaginationType::None);
    $result = $source->getData(makeIterableParams([
        'filters' => [
            ['column' => 'price', 'mode' => Filter::MODE_RANGE, 'value' => ['from' => 30, 'to' => 600], 'type' => Filter::TYPE_NUMBER],
        ],
    ]));

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(3)
        ->and($result->pluck('name')->sort()->values()->all())->toBe(['Ergonomic Chair', 'Laptop Stand', 'Office Desk']);
});

it('filters with range mode using only from', function () {
    $source = new IterableDataSource(iterableProducts(), DataSourcePaginationType::None);
    $result = $source->getData(makeIterableParams([
        'filters' => [
            ['column' => 'price', 'mode' => Filter::MODE_RANGE, 'value' => ['from' => 500], 'type' => Filter::TYPE_NUMBER],
        ],
    ]));

    expect($result)->toHaveCount(2)
        ->and($result->pluck('name')->sort()->values()->all())->toBe(['Ergonomic Chair', 'Laptop Pro']);
});

it('filters with range mode using only to', function () {
    $source = new IterableDataSource(iterableProducts(), DataSourcePaginationType::None);
    $result = $source->getData(makeIterableParams([
        'filters' => [
            ['column' => 'price', 'mode' => Filter::MODE_RANGE, 'value' => ['to' => 30], 'type' => Filter::TYPE_NUMBER],
        ],
    ]));

    expect($result)->toHaveCount(2)
        ->and($result->pluck('name')->sort()->values()->all())->toBe(['USB Cable', 'Wireless Mouse']);
});

it('returns empty result when range matches nothing', function () {
    $source = new IterableDataSource(iterableProducts(), DataSourcePaginationType::None);
    $result = $source->getData(makeIterableParams([
        'filters' => [
            ['column' => 'price', 'mode' => Filter::MODE_RANGE, 'value' => ['from' => 5000, 'to' => 9000], 'type' => Filter::TYPE_NUMBER],
        ],
    ]));

    expect($result)->toHaveCount(0);
});
