<?php

use ErickComp\LivewireDataTable\Data\CallableDataSource;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use Tests\Fixtures\TestCallableDataProvider;

// --- Validation ---

it('throws on closure data source', function () {
    new CallableDataSource(fn() => [], DataSourcePaginationType::None);
})->throws(\ValueError::class);

it('throws on invalid callable string', function () {
    new CallableDataSource('nonexistent_function', DataSourcePaginationType::None);
})->throws(\ValueError::class);

// --- Callable type detection ---

it('accepts a static method string with :: notation', function () {
    $source = new CallableDataSource(
        TestCallableDataProvider::class . '::getStaticData',
        DataSourcePaginationType::None,
    );

    expect($source)->toBeInstanceOf(CallableDataSource::class);
});

it('accepts a class@method string', function () {
    $source = new CallableDataSource(
        TestCallableDataProvider::class . '@getData',
        DataSourcePaginationType::None,
    );

    expect($source)->toBeInstanceOf(CallableDataSource::class);
});

