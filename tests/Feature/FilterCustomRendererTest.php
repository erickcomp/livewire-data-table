<?php

use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\View\ComponentAttributeBag;

it('renders custom renderer code with x-model and name attributes on select', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
        ]),
        customRendererCode: '<select><option value="">All</option><option value="1">Active</option><option value="0">Inactive</option></select>',
    );

    $result = $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($result)->toContain('x-model')
        ->and($result)->toContain('name=');
});

it('extracts select options from custom renderer HTML', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
        ]),
        customRendererCode: '<select><option value="">Choose</option><option value="1">Active</option><option value="0">Inactive</option></select>',
    );

    $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($filter->getSelectOptions())->toBe([
        '' => 'Choose',
        '1' => 'Active',
        '0' => 'Inactive',
    ]);
});

it('does not overwrite explicit options prop with extracted options', function () {
    $explicitOptions = ['a' => 'Alpha', 'b' => 'Beta'];

    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
            'options' => $explicitOptions,
        ]),
        customRendererCode: '<select><option value="1">One</option></select>',
    );

    $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($filter->getSelectOptions())->toBe($explicitOptions);
});

it('caches rendered custom code on second call', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
        ]),
        customRendererCode: '<select><option value="1">Yes</option></select>',
    );

    $first = $filter->getCustomRendererCodeWithXModel('inputFilters');
    $second = $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($first)->toBe($second);
});

it('throws when select custom renderer has no select element', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
        ]),
        customRendererCode: '<input type="text" />',
    );

    $filter->getCustomRendererCodeWithXModel('inputFilters');
})->throws(\LogicException::class);

it('throws when select custom renderer has no options and no explicit options prop', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
        ]),
        customRendererCode: '<select></select>',
    );

    $filter->getCustomRendererCodeWithXModel('inputFilters');
})->throws(\LogicException::class);

it('inserts name attribute into input element', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'query',
            'name' => 'query',
            'input-type' => Filter::TYPE_TEXT,
            'label' => 'Query',
        ]),
        customRendererCode: '<input type="text" />',
    );

    $result = $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($result)->toContain('name=')
        ->and($result)->toContain('x-model=')
        ->and($result)->toContain('x-on:keydown.enter');
});
