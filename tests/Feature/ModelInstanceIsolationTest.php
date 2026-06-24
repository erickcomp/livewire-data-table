<?php

use ErickComp\LivewireDataTable\Data\EloquentDataSource;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use Illuminate\Database\Eloquent\Model;

class ModelA extends Model
{
    protected $table = 'model_a_table';
    protected $perPage = 10;
}

class ModelB extends Model
{
    protected $table = 'model_b_table';
    protected $perPage = 25;
}

it('does not share model instances across different EloquentDataSource instances', function () {
    $sourceA = new EloquentDataSource(ModelA::class, DataSourcePaginationType::LengthAware);
    $sourceB = new EloquentDataSource(ModelB::class, DataSourcePaginationType::LengthAware);

    expect($sourceA->modelPerPage())->toBe(10)
        ->and($sourceB->modelPerPage())->toBe(25);
});

it('returns consistent model instance on repeated calls', function () {
    $source = new EloquentDataSource(ModelA::class, DataSourcePaginationType::LengthAware);

    $perPage1 = $source->modelPerPage();
    $perPage2 = $source->modelPerPage();

    expect($perPage1)->toBe(10)
        ->and($perPage2)->toBe(10);
});
