<?php

use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Data\QueryBuilderDataSource;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\ComponentAttributeBag;
use Tests\Fixtures\TestProduct;

uses(RefreshDatabase::class);

function seedQueryBuilderProducts(): void
{
    TestProduct::insert([
        ['name' => 'Laptop Pro', 'category' => 'electronics', 'price' => 1299.99, 'created_at' => '2024-01-15 10:00:00', 'updated_at' => '2024-01-15 10:00:00'],
        ['name' => 'Wireless Mouse', 'category' => 'electronics', 'price' => 29.99, 'created_at' => '2024-02-10 10:00:00', 'updated_at' => '2024-02-10 10:00:00'],
        ['name' => 'Office Desk', 'category' => 'furniture', 'price' => 349.00, 'created_at' => '2024-03-05 10:00:00', 'updated_at' => '2024-03-05 10:00:00'],
        ['name' => 'Ergonomic Chair', 'category' => 'furniture', 'price' => 599.00, 'created_at' => '2024-04-20 10:00:00', 'updated_at' => '2024-04-20 10:00:00'],
    ]);
}

function makeQueryBuilderParams(array $overrides = []): LwDataRetrievalParams
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

it('retrieves all rows with no pagination', function () {
    seedQueryBuilderProducts();

    $source = new QueryBuilderDataSource(DB::table('test_products'), DataSourcePaginationType::None);
    $result = $source->getData(makeQueryBuilderParams());

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(4);
});

it('filters with exact mode on query builder', function () {
    seedQueryBuilderProducts();

    $source = new QueryBuilderDataSource(DB::table('test_products'), DataSourcePaginationType::None);
    $result = $source->getData(makeQueryBuilderParams([
        'filters' => [
            ['column' => 'category', 'mode' => Filter::MODE_EXACT, 'value' => 'furniture', 'type' => Filter::TYPE_TEXT],
        ],
    ]));

    expect($result)->toHaveCount(2)
        ->and($result->pluck('name')->sort()->values()->all())->toBe(['Ergonomic Chair', 'Office Desk']);
});

it('filters with contains mode on query builder', function () {
    seedQueryBuilderProducts();

    $source = new QueryBuilderDataSource(DB::table('test_products'), DataSourcePaginationType::None);
    $result = $source->getData(makeQueryBuilderParams([
        'filters' => [
            ['column' => 'name', 'mode' => Filter::MODE_CONTAINS, 'value' => 'Laptop', 'type' => Filter::TYPE_TEXT],
        ],
    ]));

    expect($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('Laptop Pro');
});

it('filters with range mode on query builder', function () {
    seedQueryBuilderProducts();

    $source = new QueryBuilderDataSource(DB::table('test_products'), DataSourcePaginationType::None);
    $result = $source->getData(makeQueryBuilderParams([
        'filters' => [
            ['column' => 'price', 'mode' => Filter::MODE_RANGE, 'value' => ['from' => '100', 'to' => '600'], 'type' => Filter::TYPE_NUMBER],
        ],
    ]));

    expect($result)->toHaveCount(2)
        ->and($result->pluck('name')->sort()->values()->all())->toBe(['Ergonomic Chair', 'Office Desk']);
});

it('paginates with length-aware pagination on query builder', function () {
    seedQueryBuilderProducts();

    $source = new QueryBuilderDataSource(DB::table('test_products'), DataSourcePaginationType::LengthAware);
    $result = $source->getData(makeQueryBuilderParams(['perPage' => '2']));

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(2)
        ->and($result->total())->toBe(4);
});
