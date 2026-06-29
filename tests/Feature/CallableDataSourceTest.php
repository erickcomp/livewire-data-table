<?php

use ErickComp\LivewireDataTable\Data\CallableDataSource;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Support\Collection;
use Tests\Fixtures\TestCallableDataProvider;

function makeCallableParams(array $overrides = []): LwDataRetrievalParams
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

it('retrieves data from a static method callable', function () {
    $source = new CallableDataSource(
        TestCallableDataProvider::class . '::getStaticData',
        DataSourcePaginationType::None,
    );

    $result = $source->getData(makeCallableParams());

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(3);
});

it('retrieves data from an instance method callable', function () {
    $source = new CallableDataSource(
        TestCallableDataProvider::class . '@getData',
        DataSourcePaginationType::None,
    );

    $result = $source->getData(makeCallableParams());

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(3);
});
