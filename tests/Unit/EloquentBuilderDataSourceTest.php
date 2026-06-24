<?php

use ErickComp\LivewireDataTable\Data\EloquentBuilderDataSource;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;

it('returns a class string from modelClass', function () {
    $reflection = new ReflectionClass(EloquentBuilderDataSource::class);
    $method = $reflection->getMethod('modelClass');

    expect($method->getReturnType()->getName())->toBe('string');

    // Verify the trait's abstract contract is satisfied
    $traitMethod = new ReflectionMethod(
        EloquentBuilderDataSource::class,
        'modelClass',
    );

    expect($traitMethod->hasReturnType())->toBeTrue()
        ->and($traitMethod->getReturnType()->getName())->toBe('string');
});
