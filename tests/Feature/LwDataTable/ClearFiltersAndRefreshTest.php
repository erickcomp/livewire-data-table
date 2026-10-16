<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use ErickComp\LivewireDataTable\Selection\Selection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;
use Tests\Fixtures\TestProduct;

uses(RefreshDatabase::class);

beforeEach(function () {
    TestProduct::insert([
        ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'electronics', 'price' => 1299.99, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Wireless Mouse', 'category' => 'electronics', 'price' => 29.99, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'name' => 'Office Desk', 'category' => 'furniture', 'price' => 349.00, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 4, 'name' => 'Ergonomic Chair', 'category' => 'furniture', 'price' => 599.00, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 5, 'name' => 'Bookshelf', 'category' => 'furniture', 'price' => 129.00, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

function clearAndRefreshDataTable(array $options = []): DataTable
{
    $dataTable = new DataTable(
        dataSrc: TestProduct::class,
        preset: 'empty',
        perPage: $options['perPage'] ?? '10',
        dataSrcPagination: $options['pagination'] ?? 'length_aware',
        selectable: $options['selectable'] ?? false,
        preserveSelectionOnFilter: $options['preserveSelectionOnFilter'] ?? false,
    );

    $dataTable->tableAttributes = new ComponentAttributeBag($options['tableAttributes'] ?? []);
    $dataTable->search = new Search(new ComponentAttributeBag(['data-fields' => 'name']));

    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => 'Name',
        'data-field' => 'name',
    ])));

    $dataTable->addFilter(new ComponentAttributeBag(['data-field' => 'category', 'label' => 'Category', 'mode' => 'exact']));
    $dataTable->addFilter(new ComponentAttributeBag(['data-field' => 'price', 'label' => 'Price', 'input-type' => 'number', 'mode' => 'range']));

    return $dataTable;
}

function addProduct(string $name): void
{
    TestProduct::insert(['id' => 6, 'name' => $name, 'category' => 'garden', 'price' => 10, 'created_at' => now(), 'updated_at' => now()]);
}

// --- Clear all ---

it('clears the search and all the filters and goes back to the first page', function () {
    // perPage is not aligned with the options yet (see T6 in docs/plans/magento-grid-parity.md)
    Livewire::test(LwDataTable::class, ['dataTable' => clearAndRefreshDataTable(['perPage' => '1,10'])])
        ->set('perPage', '1')
        ->set('search', 'e')
        ->call('applyFilters', ['category' => ['category' => 'furniture'], 'price' => ['price' => ['from' => '100', 'to' => '']]])
        ->call('gotoPage', 2, 'page')
        ->assertSet('paginators.page', 2)
        ->call('clearAllFilters')
        ->assertSet('search', '')
        ->assertSet('filters', [])
        ->assertSet('rawFilters', [])
        ->assertSet('paginators.page', 1)
        ->assertSee('Laptop Pro');
});

it('shows the clear all button only when there are active filters', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => clearAndRefreshDataTable()])
        ->assertDontSeeHtml('x-on:click="clearAllFilters()"')
        ->assertDontSee('Clear all');

    $component->set('search', 'desk')
        ->assertSeeHtml('x-on:click="clearAllFilters()"')
        ->assertSee('Clear all')
        ->set('search', '')
        ->assertDontSee('Clear all')
        ->call('applyFilters', ['category' => ['category' => 'furniture']])
        ->assertSee('Clear all')
        ->call('clearAllFilters')
        ->assertDontSee('Clear all');
});

it('translates the clear all button', function () {
    app()->setLocale('pt_BR');

    Livewire::test(LwDataTable::class, ['dataTable' => clearAndRefreshDataTable()])
        ->set('search', 'desk')
        ->assertSee('Limpar tudo');
});

it('drops the selection like any other change of the filters', function (bool $preserveSelectionOnFilter, array $expectedIds) {
    Livewire::test(LwDataTable::class, ['dataTable' => clearAndRefreshDataTable(['selectable' => true, 'preserveSelectionOnFilter' => $preserveSelectionOnFilter])])
        ->call('applyFilters', ['category' => ['category' => 'furniture']])
        ->set('selectedIds', ['3', '4'])
        ->call('clearAllFilters')
        ->assertSet('selectedIds', $expectedIds);
})->with([
    'by default' => [false, []],
    'with preserve-selection-on-filter' => [true, ['3', '4']],
]);

it('always drops a selection of all the matching rows', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => clearAndRefreshDataTable(['selectable' => true, 'preserveSelectionOnFilter' => true])])
        ->call('applyFilters', ['category' => ['category' => 'furniture']])
        ->call('selectAllMatching')
        ->assertSet('selectionMode', Selection::MODE_ALL)
        ->call('clearAllFilters')
        ->assertSet('selectionMode', Selection::MODE_IDS)
        ->assertSet('selectedIds', []);
});

it('hands the browser the empty filter inputs, shaped like the initial ones', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => clearAndRefreshDataTable()])
        ->call('applyFilters', ['category' => ['category' => 'furniture'], 'price' => ['price' => ['from' => '100', 'to' => '']]]);

    expect(\Livewire\invade($component->instance())->emptyFilterInputs())->toBe([
        'category' => ['category' => ''],
        'price' => ['price' => ['from' => '', 'to' => '']],
    ]);
});

// --- Refresh event ---

it('re-renders every table on the refresh event without a table', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => clearAndRefreshDataTable()])
        ->assertDontSee('Garden Hose');

    addProduct('Garden Hose');

    $component->dispatch('lw-dt-refresh')
        ->assertSee('Garden Hose');
});

it('re-renders the table named by the refresh event', function (string $table) {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => clearAndRefreshDataTable(['tableAttributes' => ['id' => 'products', 'name' => 'products-table']])]);

    addProduct('Garden Hose');

    $component->dispatch('lw-dt-refresh', table: $table)
        ->assertSee('Garden Hose');
})->with([
    'by id' => ['products'],
    'by name' => ['products-table'],
]);

it('leaves the other tables alone', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => clearAndRefreshDataTable(['tableAttributes' => ['id' => 'products']])]);

    addProduct('Garden Hose');

    $component->dispatch('lw-dt-refresh', table: 'users')
        ->assertDontSee('Garden Hose');
});

it('recounts a selection of all the matching rows on refresh', function () {
    // Simple pagination does not tell the total, so render() cannot recount it
    $component = Livewire::test(LwDataTable::class, ['dataTable' => clearAndRefreshDataTable(['selectable' => true, 'pagination' => 'simple'])])
        ->call('selectAllMatching')
        ->assertSet('matchingTotal', 5);

    addProduct('Garden Hose');

    $component->dispatch('lw-dt-refresh')
        ->assertSet('matchingTotal', 6);
});
