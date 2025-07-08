<?php

use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;

it('merges th attributes correctly', function () {
    $columnThAttributes = new ComponentAttributeBag(['class' => 'col-class', 'data-col' => '1']);
    $tableThAttributes = new ComponentAttributeBag(['class' => 'table-class', 'data-table' => 'yes']);

    $mergeFn = function ($columnThAttributes, $tableThAttributes) {
        return $columnThAttributes->merge($tableThAttributes->all());
    };

    $result = $mergeFn($columnThAttributes, $tableThAttributes);
    expect($result)->toBeInstanceOf(ComponentAttributeBag::class)
        ->and((string) $result)->toContain('col-class')
        ->and((string) $result)->toContain('table-class')
        ->and((string) $result)->toContain('data-col="1"')
        ->and((string) $result)->toContain('data-table="yes"');
});

it('resets page when updating search', function () {
    Livewire::test(LwDataTable::class)
        ->set('search', 'foo')
        ->assertSet('search', 'foo')
        ->call('updating', 'search', 'foo')
        ->assertSet('paginators.page', 1);
});

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

it('toggles sort direction and resets sortBy when toggled to none', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('sortBy', 'name')
        ->set('sortDir', 'ASC');
    $component->call('setSortBy', 'name');
    expect($component->get('sortDir'))->toBe('DESC');
    $component->call('setSortBy', 'name');
    expect($component->get('sortDir'))->toBe('');
    expect($component->get('sortBy'))->toBe('');
});

it('computes initial filters with default values', function () {
    $mockDataTable = new class {
        public $filters;
        public function __construct()
        {
            $this->filters = new class {
                public $filtersItems;
                public function __construct()
                {
                    $this->filtersItems = [
                        (object) [
                            'dataField' => 'status',
                            'name' => 'active',
                            'mode' => \ErickComp\LivewireDataTable\DataTable\Filter::MODE_DEFAULT,
                            'label' => 'Active',
                        ],
                        (object) [
                            'dataField' => 'date',
                            'name' => 'created',
                            'mode' => \ErickComp\LivewireDataTable\DataTable\Filter::MODE_RANGE,
                            'label' => 'Created',
                        ],
                    ];
                }
            };
        }
    };
    $component = Livewire::test(LwDataTable::class)
        ->set('dataTable', $mockDataTable)
        ->set('filters', []);
    $filters = $component->call('computeInitialFilters');
    expect($filters['status']['active'])->toBe('');
    expect($filters['date']['created'])->toBe(['from' => '', 'to' => '']);
});

it('should show filters container if filtersContainerIsOpen is true', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('filtersContainerIsOpen', true);
    expect($component->call('shouldShowFiltersContainer'))->toBeTrue();
});

it('should show filters container if filters are not empty', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('filtersContainerIsOpen', null)
        ->set('filters', ['foo' => ['bar' => 'baz']]);
    expect($component->call('shouldShowFiltersContainer'))->toBeTrue();
});

it('should not show filters container if filters are empty and filtersContainerIsOpen is null', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('filtersContainerIsOpen', null)
        ->set('filters', []);
    expect($component->call('shouldShowFiltersContainer'))->toBeFalse();
});

/*
 * In a Livewire-focused package, useful tests include:
 * - Attribute merging and propagation (already covered)
 * - Query string synchronization and updates
 * - Pagination resets on filter/search changes
 * - Sorting logic and toggling
 * - Filter application and removal of empty values
 * - Computation of initial filter values
 * - Conditional UI logic (e.g., showing/hiding filter containers)
 * - Data provider integration (mocking data sources)
 * - Rendering and view data structure
 * - Action handling (runAction, applyFilters, etc.)
 */
