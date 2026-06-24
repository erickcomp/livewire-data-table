<?php

use ErickComp\LivewireDataTable\Data\DataSourceFactory;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Filters;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use ErickComp\LivewireDataTable\Livewire\Preset;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;

it('resets page when updating search', function () {
    Livewire::test(LwDataTable::class)
        ->set('search', 'foo')
        ->assertSet('search', 'foo')
        ->assertSet('paginators.page', 1);
});

it('resets page when updating filters', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('paginators', ['page' => 5])
        ->set('filters', ['status' => ['active' => 'yes']]);

    expect($component->get('paginators.page'))->toBe(1);
});

it('resets page when updating columnsSearch nested values', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('paginators', ['page' => 5])
        ->set('columnsSearch.name', 'john');

    expect($component->get('paginators.page'))->toBe(1);
});

it('resets page when updating perPage', function () {
    $component = Livewire::test(LwDataTable::class);
    $originalPage = $component->get('paginators.page');
    $originalPerPage = $component->get('perPage');

    $component->set('paginators', ['page' => 3]);
    expect($component->get('paginators.page'))->toBe(3);

    $component->set('perPage', $originalPerPage + 1);
    expect($component->get('paginators.page'))->toBe($originalPage);
});

it('resets page when updating sort by', function () {
    $component = Livewire::test(LwDataTable::class);
    $originalPage = $component->get('paginators.page');

    $component->set('paginators', ['page' => 3]);
    expect($component->get('paginators.page'))->toBe(3);

    $component->call('setSortBy', 'some column', 'ASC');
    expect($component->get('paginators.page'))->toBe($originalPage);
});

it('resets page when updating sort direction', function () {
    $component = Livewire::test(LwDataTable::class);
    $originalPage = $component->get('paginators.page');
    $originalSortBy = $component->get('sortBy');
    $originalSortDir = $component->get('sortDir');

    $component->set('paginators', ['page' => 3]);
    expect($component->get('paginators.page'))->toBe(3);

    $component->call('setSortBy', $originalSortBy, $originalSortDir === 'ASC' ? 'DESC' : 'ASC');
    expect($component->get('paginators.page'))->toBe($originalPage);
});

it('handles page reset when paginated results exceed lastPage', function () {
    $data = collect([
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
        ['id' => 4],
        ['id' => 5],
        ['id' => 6],
        ['id' => 7],
        ['id' => 8],
        ['id' => 9],
        ['id' => 10],
    ]);

    $mockDataTable = new DataTable(paginationView: 'bootstrap', pageName: 'page');
    $mockFilters = new Filters(new ComponentAttributeBag(['collapsible' => false]), Preset::loadFromName('empty'));
    $mockFilters->filtersItems = [];

    $mockDataTable->filters = $mockFilters;
    $mockDataTable->dataSrc = DataSourceFactory::new()->make($data, DataSourcePaginationType::LengthAware);

    $component = Livewire::test(LwDataTable::class, ['data-table' => $mockDataTable]);
    $component->instance()->perPage = 3;
    $component->set([
        'filters' => [],
        'search' => '',
        'paginators' => ['page' => 5],
    ]);

    $component->instance()->render();

    $component->assertSet('paginators.page', 1);
});
