<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Filters;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use ErickComp\LivewireDataTable\Livewire\Preset;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;

it('applies filters and removes empty values', function () {
    $component = Livewire::test(LwDataTable::class);
    $filters = [
        'status' => ['active' => '', 'pending' => 'yes'],
        'type' => ['admin' => null, 'user' => ''],
        'role' => ['editor' => '1'],
    ];
    $component->call('applyFilters', $filters);
    $applied = $component->get('filters');
    expect($applied)->toBe([
        'status' => ['pending' => 'yes'],
        'role' => ['editor' => '1'],
    ]);
});

it('preserves zero and false values when applying filters', function () {
    $component = Livewire::test(LwDataTable::class);

    $component->call('applyFilters', [
        'priority' => ['low' => 0, 'high' => '', 'medium' => false],
        'tags' => ['active' => [], 'new' => 'yes'],
    ]);

    expect($component->get('filters'))->toBe([
        'priority' => ['low' => 0, 'medium' => false],
        'tags' => ['new' => 'yes'],
    ]);
});

it('preserves range filter from and to values when applying filters', function () {
    $component = Livewire::test(LwDataTable::class);

    $component->call('applyFilters', [
        'date' => ['from' => '2024-01-01', 'to' => '2024-01-31'],
        'status' => ['active' => 'yes'],
    ]);

    expect($component->get('filters'))->toBe([
        'date' => ['from' => '2024-01-01', 'to' => '2024-01-31'],
        'status' => ['active' => 'yes'],
    ]);
    expect($component->get('rawFilters'))->toBe([
        'date' => ['from' => '2024-01-01', 'to' => '2024-01-31'],
        'status' => ['active' => 'yes'],
    ]);
});

it('should show filters container when component is not collapsible', function () {
    $mockDataTable = new DataTable();

    $component = Livewire::test(LwDataTable::class, ['data-table' => $mockDataTable]);

    $component->set([
        'filtersContainerIsOpen' => null,
        'filters' => [],
    ]);

    expect($component->instance()->shouldShowFiltersContainer())->toBeTrue();
});

it('should show filters container if filtersContainerIsOpen is true', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('filtersContainerIsOpen', true);
    expect($component->instance()->shouldShowFiltersContainer())->toBeTrue();
});

it('should show filters container if filters are not empty', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('filtersContainerIsOpen', null)
        ->set('filters', ['foo' => ['bar' => 'baz']]);
    expect($component->instance()->shouldShowFiltersContainer())->toBeTrue();
});

it('hides filters container when collapsible and explicitly closed', function () {
    $mockDataTable = new DataTable();
    $mockDataTable->filters = new Filters(
        new ComponentAttributeBag(['collapsible' => true]),
        Preset::loadFromName('empty'),
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $mockDataTable])
        ->set('filtersContainerIsOpen', false)
        ->set('filters', []);

    expect($component->instance()->shouldShowFiltersContainer())->toBeFalse();
});

it('shows filters container when collapsible and open state is undefined', function () {
    $mockDataTable = new DataTable();
    $mockDataTable->filters = new Filters(
        new ComponentAttributeBag(['collapsible' => true]),
        Preset::loadFromName('empty'),
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $mockDataTable])
        ->set('filtersContainerIsOpen', null)
        ->set('filters', []);

    expect($component->instance()->shouldShowFiltersContainer())->toBeTrue();
});

it('keeps existing filter values when computing initial filters', function () {
    $mockDataTable = new DataTable();
    $mockFilters = new Filters(new ComponentAttributeBag(), Preset::loadFromName('empty'));
    $mockFilters->filtersItems = [
        new Filter(
            new ComponentAttributeBag([
                'data-field' => 'status',
                'name' => 'active',
                'mode' => Filter::MODE_EXACT,
                'label' => 'Active',
            ]),
        ),
    ];

    $mockDataTable->filters = $mockFilters;

    $component = Livewire::test(LwDataTable::class, ['data-table' => $mockDataTable]);
    $component->set('filters', ['status' => ['active' => '1']]);

    $initialFilters = $component->invade()->computeInitialFilters();

    expect($initialFilters['status']['active'])->toBe('1');
});

it('computes initial filters with default values', function () {
    $mockDataTable = new DataTable();
    $mockFilters = new Filters(new ComponentAttributeBag(), Preset::loadFromName('empty'));
    $mockFilters->filtersItems = [
        new Filter(
            new ComponentAttributeBag([
                'data-field' => 'status',
                'name' => 'active',
                'mode' => Filter::MODE_EXACT,
                'label' => 'Active',
            ]),
        ),
        new Filter(
            new ComponentAttributeBag([
                'data-field' => 'date',
                'name' => 'created',
                'mode' => Filter::MODE_RANGE,
                'label' => 'Created',
            ]),
        ),
    ];

    $mockDataTable->filters = $mockFilters;

    $component = Livewire::test(LwDataTable::class, ['data-table' => $mockDataTable]);

    $component->set('filters', []);
    $filters = $component->instance()->computeInitialFilters();
    expect($filters['status']['active'])->toBe('')
        ->and($filters['date']['created'])->toBe(['from' => '', 'to' => '']);
});
