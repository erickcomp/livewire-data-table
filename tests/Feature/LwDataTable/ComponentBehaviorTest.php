<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Filters;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use ErickComp\LivewireDataTable\Livewire\Preset;
use Illuminate\Pagination\LengthAwarePaginator;
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

it('falls back gracefully when the data table cache is unavailable', function () {
    $component = Livewire::test(LwDataTable::class);
    $component->invade()->dt = 'does-not-exist';

    $reflection = new ReflectionClass($component->instance());
    $method = $reflection->getMethod('hydrateDataTable');

    $result = $method->invoke($component->instance());

    expect($result)->toBeNull();
});

it('returns default Livewire query string parameter names', function () {
    $mockDataTable = new DataTable();

    $component = Livewire::test(LwDataTable::class, ['data-table' => $mockDataTable]);

    expect($component->instance()->filtersUrlParam())->toBe('filters');

    $reflection = new ReflectionClass($component->instance());
    $pageNameMethod = $reflection->getMethod('pageNameUrlParam');
    $searchNameMethod = $reflection->getMethod('searchUrlParam');
    $columnsSearchNameMethod = $reflection->getMethod('columnsSearchUrlParam');

    expect($pageNameMethod->invoke($component->instance()))->toBe('page')
        ->and($searchNameMethod->invoke($component->instance()))->toBe('search')
        ->and($columnsSearchNameMethod->invoke($component->instance()))->toBe('cols-search');
});

it('delegates runAction to the underlying data table instance', function () {
    $called = [];

    $mockDataTable = new class ($called) extends DataTable {
        public $called;

        public function __construct(&$called)
        {
            $this->called = &$called;
            parent::__construct();
        }

        public function runAction(string $action, ...$params)
        {
            $this->called = [$action, $params];
        }
    };

    $component = Livewire::test(LwDataTable::class);
    $component->invade()->dataTable = $mockDataTable;
    $component->invade()->runAction('delete', 42);

    expect($called[0])->toBe('delete')
        ->and($called[1])->toBe([42]);
});

it('allows columns search only when more than one row exists', function () {
    $component = Livewire::test(LwDataTable::class);

    expect($component->instance()->shouldAllowColumnsSearch(collect([1, 2])))->toBeTrue()
        ->and($component->instance()->shouldAllowColumnsSearch(collect([1])))->toBeFalse();
});

it('renders view with rows and initialFilters data', function () {
    $mockDataTable = new DataTable(paginationView: 'bootstrap');
    $mockFilters = new Filters(new ComponentAttributeBag(['collapsible' => false]), Preset::loadFromName('empty'));
    $mockFilters->filtersItems = [];
    $mockDataTable->filters = $mockFilters;

    $component = Livewire::test(LwDataTable::class, ['data-table' => $mockDataTable]);
    $component->set('filters', []);

    $view = $component->instance()->render();

    expect($view)->not()->toBeNull();
});

it('validates that renderPagination handles different paginator types', function () {
    $collection = collect([['id' => 1]]);
    $paginator = new LengthAwarePaginator($collection, 10, 15, 2);

    $mockDataTable = new DataTable(paginationView: 'bootstrap', pageName: 'page');
    $mockFilters = new Filters(new ComponentAttributeBag(['collapsible' => false]), Preset::loadFromName('empty'));
    $mockDataTable->filters = $mockFilters;

    $component = Livewire::test(LwDataTable::class, ['data-table' => $mockDataTable]);
    $resultCollection = $component->instance()->renderPagination($collection);
    $resultPaginator = $component->instance()->renderPagination($paginator);

    expect($resultCollection)->toBe('')
        ->and($resultPaginator)->not()->toBe('');
});

it('validates that isDataPaginated detects cursor and length aware paginators', function () {
    $component = Livewire::test(LwDataTable::class);

    $collection = collect([['id' => 1]]);
    $lengthAwarePaginator = new LengthAwarePaginator($collection, 10, 15, 1);

    expect($component->instance()->isDataPaginated($collection))->toBeFalse()
        ->and($component->instance()->isDataPaginated($lengthAwarePaginator))->toBeTrue();
});
