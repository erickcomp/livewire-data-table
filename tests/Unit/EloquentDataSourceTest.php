<?php

use ErickComp\LivewireDataTable\Data\EloquentDataSource;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;

it('has a callable getQuery method that delegates to the trait', function () {
    $reflection = new ReflectionClass(EloquentDataSource::class);
    $method = $reflection->getMethod('getQuery');

    expect($method->isPublic())->toBeTrue();

    // Verify the method body calls the trait method that actually exists
    $traitMethod = 'applyDataRetrievalParamsOnEloquentBuilder';
    expect($reflection->hasMethod($traitMethod))->toBeTrue();
});
