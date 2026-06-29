<?php

use ErickComp\LivewireDataTable\Data\ValuesCaster;
use ErickComp\LivewireDataTable\DataTable\Filter;

it('casts text filter values to string', function () {
    $filter = ['type' => Filter::TYPE_TEXT, 'value' => 123];

    $result = ValuesCaster::castValueFromFilter($filter);

    expect($result)->toBe('123')
        ->and($result)->toBeString();
});

it('casts number filter values to int', function () {
    $filter = ['type' => Filter::TYPE_NUMBER, 'value' => '42'];

    $result = ValuesCaster::castValueFromFilter($filter);

    expect($result)->toBe(42)
        ->and($result)->toBeInt();
});

it('casts float number filter values to float', function () {
    $filter = ['type' => Filter::TYPE_NUMBER, 'value' => '3.14'];

    $result = ValuesCaster::castValueFromFilter($filter);

    expect($result)->toBe(3.14)
        ->and($result)->toBeFloat();
});

it('returns raw value for select filter type', function () {
    $filter = ['type' => Filter::TYPE_SELECT, 'value' => 'active'];

    $result = ValuesCaster::castValueFromFilter($filter);

    expect($result)->toBe('active');
});

it('returns raw value for select-multiple filter type', function () {
    $filter = ['type' => Filter::TYPE_SELECT_MULTIPLE, 'value' => ['a', 'b']];

    $result = ValuesCaster::castValueFromFilter($filter);

    expect($result)->toBe(['a', 'b']);
});

it('extracts range values correctly', function () {
    $filter = ['type' => Filter::TYPE_NUMBER, 'value' => ['from' => '10', 'to' => '20']];

    $from = ValuesCaster::castValueFromFilter($filter, 'from');
    $to = ValuesCaster::castValueFromFilter($filter, 'to');

    expect($from)->toBe(10)
        ->and($to)->toBe(20);
});

it('does not return null for non-cast filter values', function () {
    $filter = ['type' => Filter::TYPE_TEXT, 'value' => 'hello'];

    $result = ValuesCaster::castValueFromFilter($filter);

    expect($result)->not->toBeNull()
        ->and($result)->toBe('hello');
});

it('throws on invalid range parameter', function () {
    $filter = ['type' => Filter::TYPE_TEXT, 'value' => 'test'];

    ValuesCaster::castValueFromFilter($filter, 'invalid');
})->throws(LogicException::class);

it('throws on unknown filter type', function () {
    $filter = ['type' => 'unknown_type', 'value' => 'test'];

    ValuesCaster::castValueFromFilter($filter);
})->throws(UnexpectedValueException::class);

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

it('casts date filter values to Carbon instance', function () {
    $result = ValuesCaster::castValueToFilterType('2024-01-15', Filter::TYPE_DATE);

    expect($result)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($result->format('Y-m-d'))->toBe('2024-01-15');
});

it('casts datetime filter values to Carbon instance', function () {
    $result = ValuesCaster::castValueToFilterType('2024-01-15 10:30:00', Filter::TYPE_DATETIME);

    expect($result)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($result->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
});

it('casts number-range filter type same as number', function () {
    $result = ValuesCaster::castValueToFilterType('42', Filter::TYPE_NUMBER_RANGE);

    expect($result)->toBe(42);
});
