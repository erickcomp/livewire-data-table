<?php

use ErickComp\LivewireDataTable\Data\DataSourceFactory;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Data\EloquentDataSource;
use ErickComp\LivewireDataTable\Data\EmptyDataSource;
use ErickComp\LivewireDataTable\Data\IterableDataSource;
use ErickComp\LivewireDataTable\Data\CallableDataSource;
use Illuminate\Support\Collection;
use Tests\Fixtures\TestProduct;

it('creates EloquentDataSource from a model class string', function () {
    $source = DataSourceFactory::new()->make(TestProduct::class, DataSourcePaginationType::LengthAware);

    expect($source)->toBeInstanceOf(EloquentDataSource::class);
});

it('creates IterableDataSource from an array', function () {
    $source = DataSourceFactory::new()->make([['id' => 1], ['id' => 2]], DataSourcePaginationType::None);

    expect($source)->toBeInstanceOf(IterableDataSource::class);
});

it('creates IterableDataSource from a Collection', function () {
    $source = DataSourceFactory::new()->make(collect([['id' => 1]]), DataSourcePaginationType::None);

    expect($source)->toBeInstanceOf(IterableDataSource::class);
});

it('creates EmptyDataSource from null', function () {
    $source = DataSourceFactory::new()->make(null, DataSourcePaginationType::None);

    expect($source)->toBeInstanceOf(EmptyDataSource::class);
});

it('creates EmptyDataSource from empty string', function () {
    $source = DataSourceFactory::new()->make('', DataSourcePaginationType::None);

    expect($source)->toBeInstanceOf(EmptyDataSource::class);
});

it('creates CallableDataSource from a static method string', function () {
    $source = DataSourceFactory::new()->make(TestProduct::class . '::query', DataSourcePaginationType::None);

    expect($source)->toBeInstanceOf(CallableDataSource::class);
});

it('throws on invalid data source value', function () {
    DataSourceFactory::new()->make(12345, DataSourcePaginationType::None);
})->throws(\LogicException::class);
