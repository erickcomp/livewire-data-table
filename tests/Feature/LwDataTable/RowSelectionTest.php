<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use ErickComp\LivewireDataTable\Selection\Selection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Fixtures\TestProduct;
use Tests\Fixtures\TestSelectionDataProvider;

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

function selectableDataTable(array $options = []): DataTable
{
    $dataTable = new DataTable(
        dataSrc: $options['dataSrc'] ?? TestProduct::class,
        preset: $options['preset'] ?? 'empty',
        perPage: $options['perPage'] ?? '2',
        selectable: $options['selectable'] ?? true,
        preserveSelectionOnFilter: $options['preserveSelectionOnFilter'] ?? false,
        recordsCount: $options['recordsCount'] ?? null,
    );

    $dataTable->search = new Search(new ComponentAttributeBag(['data-fields' => 'name']));

    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => 'Name',
        'data-field' => 'name',
        'searchable' => true,
    ])));

    $dataTable->addFilter(new ComponentAttributeBag(['data-field' => 'category', 'label' => 'Category', 'mode' => 'exact']));

    return $dataTable;
}

function furnitureOnly(): array
{
    return ['category' => ['category' => 'furniture']];
}

// --- Rendering ---

it('renders no selection markup on tables that are not selectable', function () {
    $html = Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable(['selectable' => false])])->html();

    expect($html)->not->toContain('type="checkbox"')
        ->not->toContain('isRowSelected')
        ->not->toContain('selectionMenuOpen')
        ->not->toContain('records found');
});

it('renders a checkbox per row, the page checkbox and the selection menu', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->set('perPage', '2')
        ->assertSeeHtml('aria-label="Select all rows on this page"')
        ->assertSeeHtml('pageRowsIds: ' . Js::from(['1', '2']))
        ->assertSeeHtml('value="1"')
        ->assertSeeHtml('x-bind:checked="isRowSelected(\'1\')"')
        ->assertSeeHtml('x-on:change="toggleRow(\'2\')"')
        ->assertDontSeeHtml('isRowSelected(\'3\')')
        ->assertSee('Select all (5)')
        ->assertSee('Deselect all')
        ->assertSee('Select all on this page')
        ->assertSee('Deselect all on this page')
        ->assertSee('5 records found');
});

it('adds the selection cell to the columns search row and to the "no data" colspan', function () {
    $html = Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable(['dataSrc' => collect()])])->html();

    expect($html)->toContain('colspan="2"');
});

it('forwards the selection-* attributes and applies the preset classes', function () {
    $dataTable = selectableDataTable(['preset' => 'vanilla']);

    (new ReflectionMethod($dataTable, 'fillComponentAttributeBags'))->invoke($dataTable, new ComponentAttributeBag([
        'selection-th-data-role' => 'selection-header',
        'selection-td-data-role' => 'selection-cell',
        'selection-checkbox-data-role' => 'selection-checkbox',
    ]));

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('data-role="selection-header"')
        ->assertSeeHtml('data-role="selection-cell"')
        ->assertSeeHtml('data-role="selection-checkbox"')
        ->assertSeeHtml('lw-dt-selection-th')
        ->assertSeeHtml('lw-dt-selection-td')
        ->assertSeeHtml('lw-dt-selection-menu')
        ->assertSeeHtml('lw-dt-records-count')
        ->assertSeeHtml('{ \'lw-dt-selected-row\': isRowSelected(\'1\') }');
});

it('counts the records on tables that are not selectable when asked to', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable(['selectable' => false, 'recordsCount' => true])])
        ->assertSee('5 records found')
        ->assertDontSeeHtml('type="checkbox"');
});

it('hides the records count when asked to', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable(['recordsCount' => 'false'])])
        ->assertDontSee('records found')
        ->assertSeeHtml('type="checkbox"');
});

it('leaves out the total when the paginator does not know it', function () {
    $dataTable = selectableDataTable(['dataSrc' => TestSelectionDataProvider::class . '@simplePaginatedBuilder']);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertDontSee('records found')
        ->assertSee('Select all')
        ->assertDontSee('Select all (');
});

it('reads boolean attributes given as strings', function () {
    $dataTable = new DataTable(selectable: 'true', preserveSelectionOnFilter: 'off', recordsCount: 'no');

    expect($dataTable->selectable)->toBeTrue()
        ->and($dataTable->preserveSelectionOnFilter)->toBeFalse()
        ->and($dataTable->showRecordsCount)->toBeFalse();
});

it('rejects a value that is not a boolean', function () {
    new DataTable(selectable: 'sometimes');
})->throws(\ValueError::class, 'Invalid value for the attribute [selectable]');

// --- Selecting ---

it('selects every row matching the current filters', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->call('applyFilters', furnitureOnly())
        ->call('selectAllMatching')
        ->assertSet('selectionMode', Selection::MODE_ALL)
        ->assertSet('selectedIds', [])
        ->assertSet('matchingTotal', 3);
});

it('keeps the count of the "all" selection current as the rows change', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->call('selectAllMatching')
        ->assertSet('matchingTotal', 5);

    TestProduct::query()->whereKey(5)->delete();

    $component->call('$refresh')->assertSet('matchingTotal', 4);
});

it('does not select all the rows of a table that is not selectable', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable(['selectable' => false])])
        ->call('selectAllMatching')
        ->assertSet('selectionMode', Selection::MODE_IDS)
        ->assertSet('matchingTotal', null);
});

it('deselects everything', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->call('selectAllMatching')
        ->set('selectedIds', ['2'])
        ->call('deselectAll')
        ->assertSet('selectionMode', Selection::MODE_IDS)
        ->assertSet('selectedIds', [])
        ->assertSet('matchingTotal', null);
});

it('does not let the browser change the selection mode or the total', function (string $property, mixed $value) {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->set($property, $value);
})->with([
    'mode' => ['selectionMode', Selection::MODE_ALL],
    'total' => ['matchingTotal', 999],
])->throws(CannotUpdateLockedPropertyException::class);

it('cleans up the selected ids at the end of the request', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->set('selectedIds', [1, '1', '', '2', null, ['3'], 4])
        ->assertSet('selectedIds', ['1', '2', '4']);
});

it('holds at most max-selected-ids ids', function () {
    config(['erickcomp-livewire-data-table.max-selected-ids' => 2]);

    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->set('selectedIds', ['1', '2', '3', '4'])
        ->assertSet('selectedIds', ['1', '2']);
});

it('resolves the selection against the data source', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->set('selectedIds', ['4', '1', '999']);

    expect(\Livewire\invade($component->instance())->selection()->ids())->toBe(['1', '4'])
        ->and(\Livewire\invade($component->instance())->selectedCount())->toBe(2);

    $component->call('applyFilters', furnitureOnly())
        ->call('selectAllMatching')
        ->set('selectedIds', ['4']);

    expect(\Livewire\invade($component->instance())->selection()->ids())->toBe(['3', '5'])
        ->and(\Livewire\invade($component->instance())->selectedCount())->toBe(2);
});

// --- Keeping or dropping the selection ---

it('drops the selection when the search, the filters or a column search change', function (Closure $change) {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->set('selectedIds', ['1', '2']);

    $change($component);

    $component->assertSet('selectedIds', [])
        ->assertSet('selectionMode', Selection::MODE_IDS);
})->with([
    'search' => [fn($component) => $component->set('search', 'desk')],
    'filters' => [fn($component) => $component->call('applyFilters', furnitureOnly())],
    'column search' => [fn($component) => $component->set('columnsSearch.name', 'desk')],
]);

it('drops the selection when the search changes in the same request as rows are checked', function (array $updates) {
    // A single request carrying both updates, in the given order
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->update(updates: $updates)
        ->assertSet('search', 'desk')
        ->assertSet('selectedIds', []);
})->with([
    'search first' => [['search' => 'desk', 'selectedIds' => ['1']]],
    'rows first' => [['selectedIds' => ['1'], 'search' => 'desk']],
]);

it('drops a selection of all the rows when the filters change', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable()])
        ->call('selectAllMatching')
        ->set('selectedIds', ['2'])
        ->call('applyFilters', furnitureOnly())
        ->assertSet('selectionMode', Selection::MODE_IDS)
        ->assertSet('selectedIds', [])
        ->assertSet('matchingTotal', null);
});

it('keeps the selection when paging, sorting or changing the rows per page', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable(['perPage' => '2,4'])])
        ->set('selectedIds', ['1', '2'])
        ->call('gotoPage', 2)
        ->call('setSortBy', 'name')
        ->set('perPage', '4')
        ->assertSet('selectedIds', ['1', '2']);
});

it('keeps the rows checked one by one when the filters change, with preserve-selection-on-filter', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable(['preserveSelectionOnFilter' => true])])
        ->set('selectedIds', ['1', '3'])
        ->set('search', 'desk')
        ->call('applyFilters', furnitureOnly())
        ->assertSet('selectedIds', ['1', '3']);
});

it('drops a selection of all the rows even with preserve-selection-on-filter', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => selectableDataTable(['preserveSelectionOnFilter' => true])])
        ->call('selectAllMatching')
        ->call('applyFilters', furnitureOnly())
        ->assertSet('selectionMode', Selection::MODE_IDS)
        ->assertSet('selectedIds', []);
});
