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
