<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Filters;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;

function renderColumn(string $title, string $dataField, bool $sortable = false, bool $searchable = false): DataColumn
{
    return DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => $title,
        'data-field' => $dataField,
        'sortable' => $sortable,
        'searchable' => $searchable,
    ]));
}

function renderDataTable(array $data, array $columns, array $options = []): DataTable
{
    $dataTable = new DataTable(
        dataSrc: collect($data),
        preset: $options['preset'] ?? 'empty',
        perPage: $options['perPage'] ?? [],
        paginationView: $options['paginationView'] ?? 'bootstrap',
    );

    foreach ($columns as $col) {
        $dataTable->columns->push($col);
    }

    return $dataTable;
}

// --- Column headers ---

it('renders column headers in th elements', function () {
    $dataTable = renderDataTable(
        [['id' => 1, 'name' => 'Product A']],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSee('ID')
        ->assertSee('Name')
        ->assertSeeHtml('<th')
        ->assertSeeHtml('<table');
});

// --- Data cells ---

it('renders row data in table cells', function () {
    $dataTable = renderDataTable(
        [
            ['id' => 1, 'name' => 'Laptop Pro'],
            ['id' => 2, 'name' => 'Wireless Mouse'],
        ],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSee('Laptop Pro')
        ->assertSee('Wireless Mouse')
        ->assertSeeHtml('<td');
});

// --- Empty state ---

it('renders no data found message when data is empty', function () {
    $dataTable = renderDataTable(
        [],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSee('No data found');
});

it('does not render no data found message when data exists', function () {
    $dataTable = renderDataTable(
        [['id' => 1, 'name' => 'Product']],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertDontSee('No data found');
});

// --- Search ---

it('renders search button when search is enabled', function () {
    $dataTable = renderDataTable(
        [['id' => 1, 'name' => 'Product']],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
    );

    $dataTable->search = new Search(new ComponentAttributeBag([
        'data-fields' => 'name',
    ]));

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSee('Search');
});

it('does not render search input when search is not configured', function () {
    $dataTable = renderDataTable(
        [['id' => 1, 'name' => 'Product']],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertDontSeeHtml('x-on:click="applySearch()"');
});

// --- Per-column search ---

it('renders column search inputs for searchable columns', function () {
    $dataTable = renderDataTable(
        [
            ['id' => 1, 'name' => 'Product A'],
            ['id' => 2, 'name' => 'Product B'],
        ],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name', searchable: true)],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSeeHtml('wire:model.live.debounce')
        ->assertSeeHtml('columnsSearch.name');
});

// --- Per-page selector ---

it('renders per-page selector when multiple options exist', function () {
    $dataTable = renderDataTable(
        [['id' => 1, 'name' => 'Product']],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
        ['perPage' => '10,25,50'],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSee('Per page')
        ->assertSeeHtml('wire:model.live="perPage"');
});

it('does not render per-page selector with a single option', function () {
    $dataTable = renderDataTable(
        [['id' => 1, 'name' => 'Product']],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
        ['perPage' => '15'],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertDontSee('Per page');
});

// --- Sorting ---

it('renders sort wire click on sortable column headers', function () {
    $dataTable = renderDataTable(
        [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ],
        [renderColumn('ID', 'id', sortable: true), renderColumn('Name', 'name')],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSeeHtml("wire:click=\"setSortBy('id')\"");
});

it('does not render sort wire click on non-sortable columns', function () {
    $dataTable = renderDataTable(
        [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertDontSeeHtml('wire:click="setSortBy');
});

// --- Filters ---

it('renders filter container with apply button when filters are configured', function () {
    $dataTable = renderDataTable(
        [['id' => 1, 'name' => 'Product', 'category' => 'electronics']],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
    );

    $dataTable->initFilters(new ComponentAttributeBag(['collapsible' => 'false']));
    $dataTable->addFilter(new ComponentAttributeBag([
        'data-field' => 'category',
        'name' => 'category',
        'label' => 'Category',
        'input-type' => Filter::TYPE_TEXT,
    ]));

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSee('Category')
        ->assertSee('Apply filters');
});

// --- Wire key ---

it('renders wire key on each row using the identity column', function () {
    $dataTable = renderDataTable(
        [
            ['id' => 42, 'name' => 'Product A'],
            ['id' => 99, 'name' => 'Product B'],
        ],
        [renderColumn('ID', 'id'), renderColumn('Name', 'name')],
    );

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSeeHtml('wire:key="42"')
        ->assertSeeHtml('wire:key="99"');
});
