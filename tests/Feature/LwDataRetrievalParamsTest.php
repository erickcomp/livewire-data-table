<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\TestProduct;

uses(RefreshDatabase::class);

function seedParamsProducts(): void
{
    TestProduct::insert([
        ['name' => 'Item A', 'category' => 'cat1', 'price' => 10, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Item B', 'category' => 'cat2', 'price' => 20, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Item C', 'category' => 'cat1', 'price' => 30, 'created_at' => now(), 'updated_at' => now()],
    ]);
}

function buildParams(array $overrides = []): LwDataRetrievalParams
{
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
        dataTable: $overrides['dataTable'] ?? new DataTable(),
    );
}

it('applies params on an Eloquent builder', function () {
    seedParamsProducts();

    $params = buildParams([
        'filters' => [
            ['column' => 'category', 'mode' => Filter::MODE_EXACT, 'value' => 'cat1', 'type' => Filter::TYPE_TEXT],
        ],
    ]);

    $result = $params->apply(TestProduct::query());

    expect($result)->toBeInstanceOf(EloquentBuilder::class)
        ->and($result->get())->toHaveCount(2);
});

it('applies params on a query builder', function () {
    seedParamsProducts();

    $params = buildParams([
        'filters' => [
            ['column' => 'category', 'mode' => Filter::MODE_EXACT, 'value' => 'cat1', 'type' => Filter::TYPE_TEXT],
        ],
    ]);

    $result = $params->apply(DB::table('test_products'));

    expect($result->get())->toHaveCount(2);
});

it('applies params on a collection', function () {
    $data = collect([
        ['name' => 'A', 'category' => 'x'],
        ['name' => 'B', 'category' => 'y'],
        ['name' => 'C', 'category' => 'x'],
    ]);

    $params = buildParams([
        'filters' => [
            ['column' => 'category', 'mode' => Filter::MODE_EXACT, 'value' => 'x', 'type' => Filter::TYPE_TEXT],
        ],
    ]);

    $result = $params->apply($data);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2);
});

it('applies params on a plain array by converting to collection', function () {
    $data = [
        ['name' => 'A', 'val' => 1],
        ['name' => 'B', 'val' => 2],
    ];

    $params = buildParams();
    $result = $params->apply($data);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2);
});

it('paginates an Eloquent builder', function () {
    seedParamsProducts();

    $params = buildParams(['perPage' => '2']);
    $result = $params->applyAndPaginate(TestProduct::query());

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->items())->toHaveCount(2)
        ->and($result->total())->toBe(3);
});

it('simple paginates an Eloquent builder', function () {
    seedParamsProducts();

    $params = buildParams(['perPage' => '2']);
    $result = $params->applyAndSimplePaginate(TestProduct::query());

    expect($result)->toBeInstanceOf(\Illuminate\Pagination\Paginator::class)
        ->and($result->items())->toHaveCount(2);
});
