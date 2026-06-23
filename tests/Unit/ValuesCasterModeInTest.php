<?php

use ErickComp\LivewireDataTable\Data\ValuesCaster;
use ErickComp\LivewireDataTable\DataTable\Filter;

it('casts each value individually for MODE_IN filters', function () {
    $filter = [
        'type' => Filter::TYPE_NUMBER,
        'value' => ['1', '2', '3'],
        'column' => 'priority',
        'mode' => Filter::MODE_IN,
    ];

    $castedValues = [];
    foreach ($filter['value'] as $v) {
        $singleValueFilter = array_merge($filter, ['value' => $v]);
        $castedValues[] = ValuesCaster::castValueFromFilter($singleValueFilter);
    }

    expect($castedValues)->toBe([1, 2, 3])
        ->and($castedValues[0])->toBeInt()
        ->and($castedValues[1])->toBeInt()
        ->and($castedValues[2])->toBeInt();
});

it('does not produce duplicates when casting MODE_IN values', function () {
    $filter = [
        'type' => Filter::TYPE_TEXT,
        'value' => ['active', 'inactive', 'pending'],
        'column' => 'status',
        'mode' => Filter::MODE_IN,
    ];

    $castedValues = [];
    foreach ($filter['value'] as $v) {
        $singleValueFilter = array_merge($filter, ['value' => $v]);
        $castedValues[] = ValuesCaster::castValueFromFilter($singleValueFilter);
    }

    expect($castedValues)->toBe(['active', 'inactive', 'pending'])
        ->and(count(array_unique($castedValues)))->toBe(3);
});
