<?php

use ErickComp\LivewireDataTable\Data\DataSourceFactory;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Data\EloquentDataSource;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Tests\Fixtures\TestProduct;

uses(RefreshDatabase::class);

function seedProducts(): void
{
    TestProduct::insert([
        ['name' => 'Laptop Pro', 'category' => 'electronics', 'price' => 1299.99, 'created_at' => '2024-01-15 10:00:00', 'updated_at' => '2024-01-15 10:00:00'],
        ['name' => 'Wireless Mouse', 'category' => 'electronics', 'price' => 29.99, 'created_at' => '2024-02-10 10:00:00', 'updated_at' => '2024-02-10 10:00:00'],
        ['name' => 'Office Desk', 'category' => 'furniture', 'price' => 349.00, 'created_at' => '2024-03-05 10:00:00', 'updated_at' => '2024-03-05 10:00:00'],
        ['name' => 'Ergonomic Chair', 'category' => 'furniture', 'price' => 599.00, 'created_at' => '2024-04-20 10:00:00', 'updated_at' => '2024-04-20 10:00:00'],
        ['name' => 'USB Cable', 'category' => 'accessories', 'price' => 9.99, 'created_at' => '2024-05-01 10:00:00', 'updated_at' => '2024-05-01 10:00:00'],
        ['name' => 'Laptop Stand', 'category' => 'accessories', 'price' => 49.99, 'created_at' => '2024-06-12 10:00:00', 'updated_at' => '2024-06-12 10:00:00'],
    ]);
}

function makeParams(array $overrides = []): LwDataRetrievalParams
{
    $dataTable = new DataTable();

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
        dataTable: $overrides['dataTable'] ?? $dataTable,
    );
}

// --- Factory routing ---

it('creates EloquentDataSource from a model class string', function () {
    $source = DataSourceFactory::new()->make(TestProduct::class, DataSourcePaginationType::LengthAware);

    expect($source)->toBeInstanceOf(EloquentDataSource::class);
});

// --- Basic data retrieval ---

it('retrieves all rows with no pagination', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams());

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(6);
});

it('retrieves paginated rows with length-aware pagination', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::LengthAware);
    $result = $source->getData(makeParams(['perPage' => '2']));

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(2)
        ->and($result->total())->toBe(6);
});

it('retrieves paginated rows with simple pagination', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::Simple);
    $result = $source->getData(makeParams(['perPage' => '3']));

    expect($result)->toBeInstanceOf(Paginator::class)
        ->and($result->items())->toHaveCount(3);
});

// --- Sorting ---

it('sorts by column ascending', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams(['sortBy' => 'price', 'sortDir' => 'ASC']));

    $prices = $result->pluck('price')->map(fn($v) => (float) $v)->all();

    expect($prices[0])->toBe(9.99)
        ->and($prices[5])->toBe(1299.99);
});

it('sorts by column descending', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams(['sortBy' => 'price', 'sortDir' => 'DESC']));

    $prices = $result->pluck('price')->map(fn($v) => (float) $v)->all();

    expect($prices[0])->toBe(1299.99)
        ->and($prices[5])->toBe(9.99);
});

// --- Filters ---

it('filters with exact mode', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams([
        'filters' => [
            ['column' => 'category', 'mode' => Filter::MODE_EXACT, 'value' => 'furniture', 'type' => Filter::TYPE_TEXT],
        ],
    ]));

    expect($result)->toHaveCount(2)
        ->and($result->pluck('name')->sort()->values()->all())->toBe(['Ergonomic Chair', 'Office Desk']);
});

it('filters with contains mode', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams([
        'filters' => [
            ['column' => 'name', 'mode' => Filter::MODE_CONTAINS, 'value' => 'Laptop', 'type' => Filter::TYPE_TEXT],
        ],
    ]));

    expect($result)->toHaveCount(2)
        ->and($result->pluck('name')->sort()->values()->all())->toBe(['Laptop Pro', 'Laptop Stand']);
});

it('filters with range mode', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams([
        'filters' => [
            ['column' => 'price', 'mode' => Filter::MODE_RANGE, 'value' => ['from' => '30', 'to' => '600'], 'type' => Filter::TYPE_NUMBER],
        ],
    ]));

    $names = $result->pluck('name')->sort()->values()->all();

    expect($result)->toHaveCount(3)
        ->and($names)->toBe(['Ergonomic Chair', 'Laptop Stand', 'Office Desk']);
});

it('filters with IN mode', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams([
        'filters' => [
            ['column' => 'category', 'mode' => Filter::MODE_IN, 'value' => ['electronics', 'accessories'], 'type' => Filter::TYPE_SELECT_MULTIPLE],
        ],
    ]));

    expect($result)->toHaveCount(4);

    $categories = $result->pluck('category')->unique()->sort()->values()->all();
    expect($categories)->toBe(['accessories', 'electronics']);
});

// --- Per-column search ---

it('applies per-column search with contains mode', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams([
        'columnsSearch' => ['name' => 'cable'],
    ]));

    expect($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('USB Cable');
});

it('applies per-column search respecting starts_with mode from column definition', function () {
    seedProducts();

    $dataTable = new DataTable();
    $dataTable->columns->push(new DataColumn('Name', 'name', searchable: Column::SEARCH_MODE_STARTS_WITH));

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);

    $result = $source->getData(makeParams([
        'columnsSearch' => ['name' => 'Laptop'],
        'dataTable' => $dataTable,
    ]));

    expect($result)->toHaveCount(2)
        ->and($result->pluck('name')->sort()->values()->all())->toBe(['Laptop Pro', 'Laptop Stand']);
});

it('applies per-column search respecting exact mode from column definition', function () {
    seedProducts();

    $dataTable = new DataTable();
    $dataTable->columns->push(new DataColumn('Category', 'category', searchable: Column::SEARCH_MODE_EXACT));

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);

    $result = $source->getData(makeParams([
        'columnsSearch' => ['category' => 'electronics'],
        'dataTable' => $dataTable,
    ]));

    expect($result)->toHaveCount(2)
        ->and($result->pluck('name')->sort()->values()->all())->toBe(['Laptop Pro', 'Wireless Mouse']);
});

it('per-column search with starts_with mode does not match mid-string', function () {
    seedProducts();

    $dataTable = new DataTable();
    $dataTable->columns->push(new DataColumn('Name', 'name', searchable: Column::SEARCH_MODE_STARTS_WITH));

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);

    $result = $source->getData(makeParams([
        'columnsSearch' => ['name' => 'Mouse'],
        'dataTable' => $dataTable,
    ]));

    expect($result)->toHaveCount(0);
});

// --- Global search ---

it('applies global search across specified fields', function () {
    seedProducts();

    $search = new Search(new ComponentAttributeBag([
        'data-fields' => ['name' => Search::SEARCH_MODE_CONTAINS, 'category' => Search::SEARCH_MODE_CONTAINS],
    ]));

    $dataTable = new DataTable();
    $dataTable->search = $search;

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams([
        'search' => 'electronics',
        'dataTable' => $dataTable,
    ]));

    expect($result)->toHaveCount(2);
});

// --- Combined operations ---

it('applies filter and sort together', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams([
        'filters' => [
            ['column' => 'category', 'mode' => Filter::MODE_EXACT, 'value' => 'electronics', 'type' => Filter::TYPE_TEXT],
        ],
        'sortBy' => 'price',
        'sortDir' => 'ASC',
    ]));

    expect($result)->toHaveCount(2)
        ->and($result->first()->name)->toBe('Wireless Mouse')
        ->and($result->last()->name)->toBe('Laptop Pro');
});

it('applies filter sort and pagination together', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::LengthAware);
    $result = $source->getData(makeParams([
        'filters' => [
            ['column' => 'price', 'mode' => Filter::MODE_RANGE, 'value' => ['from' => '10', 'to' => '1000'], 'type' => Filter::TYPE_NUMBER],
        ],
        'sortBy' => 'price',
        'sortDir' => 'DESC',
        'perPage' => '2',
        'page' => 1,
    ]));

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(2)
        ->and($result->total())->toBe(4)
        ->and($result->items()[0]->name)->toBe('Ergonomic Chair')
        ->and($result->items()[1]->name)->toBe('Office Desk');
});

// --- Empty result ---

it('returns empty result when filter matches nothing', function () {
    seedProducts();

    $source = new EloquentDataSource(TestProduct::class, DataSourcePaginationType::None);
    $result = $source->getData(makeParams([
        'filters' => [
            ['column' => 'category', 'mode' => Filter::MODE_EXACT, 'value' => 'nonexistent', 'type' => Filter::TYPE_TEXT],
        ],
    ]));

    expect($result)->toHaveCount(0);
});
