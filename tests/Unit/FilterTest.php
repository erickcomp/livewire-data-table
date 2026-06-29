<?php

use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\View\ComponentAttributeBag;

it('returns select options from attributes', function () {
    $options = ['active' => 'Active', 'inactive' => 'Inactive'];

    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'status',
        'name' => 'status',
        'input-type' => Filter::TYPE_SELECT,
        'label' => 'Status',
        'options' => $options,
    ]));

    expect($filter->getSelectOptions())->toBe($options);
});

it('returns empty array when no options are provided', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'status',
        'name' => 'status',
        'input-type' => Filter::TYPE_SELECT,
        'label' => 'Status',
    ]));

    expect($filter->getSelectOptions())->toBe([]);
});

it('returns empty array when options is not an array', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'status',
        'name' => 'status',
        'input-type' => Filter::TYPE_SELECT,
        'label' => 'Status',
        'options' => 'not-an-array',
    ]));

    expect($filter->getSelectOptions())->toBe([]);
});

it('excludes options from input attributes', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'status',
        'name' => 'status',
        'input-type' => Filter::TYPE_SELECT,
        'label' => 'Status',
        'options' => ['a' => 'A'],
    ]));

    $attrs = $filter->inputAttributes();

    expect($attrs->has('options'))->toBeFalse();
});

it('detects select input type correctly', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'status',
        'name' => 'status',
        'input-type' => Filter::TYPE_SELECT,
        'label' => 'Status',
    ]));

    expect($filter->inputType)->toBe(Filter::TYPE_SELECT);

    $multiFilter = new Filter(new ComponentAttributeBag([
        'data-field' => 'tags',
        'name' => 'tags',
        'input-type' => Filter::TYPE_SELECT_MULTIPLE,
        'label' => 'Tags',
    ]));

    expect($multiFilter->inputType)->toBe(Filter::TYPE_SELECT_MULTIPLE);
});

it('accepts number-range input type and defaults to range mode', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'price',
        'name' => 'price',
        'input-type' => Filter::TYPE_NUMBER_RANGE,
        'label' => 'Price',
    ]));

    expect($filter->inputType)->toBe(Filter::TYPE_NUMBER_RANGE)
        ->and($filter->mode)->toBe(Filter::MODE_RANGE);
});

it('rejects unknown input type', function () {
    new Filter(new ComponentAttributeBag([
        'data-field' => 'status',
        'name' => 'status',
        'input-type' => 'nonexistent',
        'label' => 'Status',
    ]));
})->throws(\InvalidArgumentException::class);

it('rejects unknown filter mode via attribute', function () {
    new Filter(new ComponentAttributeBag([
        'data-field' => 'status',
        'name' => 'status',
        'mode' => 'banana',
        'label' => 'Status',
    ]));
})->throws(\InvalidArgumentException::class);

it('rejects unknown filter mode via data-field suffix', function () {
    new Filter(new ComponentAttributeBag([
        'data-field' => 'status:banana',
        'name' => 'status',
        'label' => 'Status',
    ]));
})->throws(\InvalidArgumentException::class);

it('accepts valid filter mode via attribute', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'name',
        'name' => 'name',
        'mode' => Filter::MODE_STARTS_WITH,
        'label' => 'Name',
    ]));

    expect($filter->mode)->toBe(Filter::MODE_STARTS_WITH);
});

it('accepts valid filter mode via data-field suffix', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'name:ends_with',
        'name' => 'name',
        'label' => 'Name',
    ]));

    expect($filter->mode)->toBe(Filter::MODE_ENDS_WITH);
});

it('defaults text filter to contains mode', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'name',
        'name' => 'name',
        'input-type' => Filter::TYPE_TEXT,
    ]));

    expect($filter->mode)->toBe(Filter::MODE_CONTAINS);
});

it('defaults number filter to exact mode', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'price',
        'name' => 'price',
        'input-type' => Filter::TYPE_NUMBER,
    ]));

    expect($filter->mode)->toBe(Filter::MODE_EXACT);
});

it('defaults date filter to range mode', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'created_at',
        'name' => 'created_at',
        'input-type' => Filter::TYPE_DATE,
    ]));

    expect($filter->mode)->toBe(Filter::MODE_RANGE);
});

it('defaults select filter to exact mode', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'status',
        'name' => 'status',
        'input-type' => Filter::TYPE_SELECT,
    ]));

    expect($filter->mode)->toBe(Filter::MODE_EXACT);
});

it('defaults select-multiple filter to IN mode', function () {
    $filter = new Filter(new ComponentAttributeBag([
        'data-field' => 'tags',
        'name' => 'tags',
        'input-type' => Filter::TYPE_SELECT_MULTIPLE,
    ]));

    expect($filter->mode)->toBe(Filter::MODE_IN);
});

it('throws when mode is set both via data-field suffix and mode attribute', function () {
    new Filter(new ComponentAttributeBag([
        'data-field' => 'name:exact',
        'name' => 'name',
        'mode' => 'contains',
    ]));
})->throws(\LogicException::class);
