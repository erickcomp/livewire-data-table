<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;

// DataTable.blade.php mounts the table with <livewire:lw-data-table :data-table="$__dataTable" />, which Livewire 3
// compiles to a "dataTable" param and Livewire 4 to a "data-table" one. The compiled view outlives a switch between
// the two major versions, so whichever Livewire is installed must mount the table from both forms.

function dataTableForMountTest(): DataTable
{
    $dataTable = new DataTable(
        dataSrc: collect([['id' => 1, 'name' => 'Laptop Pro']]),
        preset: 'empty',
    );

    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => 'Name',
        'data-field' => 'name',
    ])));

    return $dataTable;
}

it('mounts the table from the param the livewire tag compiles to', function (string $paramName) {
    $html = Blade::render(
        "@livewire('lw-data-table', ['{$paramName}' => \$dataTable])",
        ['dataTable' => dataTableForMountTest()],
        deleteCachedView: true,
    );

    expect($html)->toContain('Laptop Pro')
        ->not->toContain('data-table=')
        ->not->toContain('dataTable=');
})->with(['dataTable', 'data-table']);

it('mounts the table given to Livewire::test()', function (string $paramName) {
    Livewire::test(LwDataTable::class, [$paramName => dataTableForMountTest()])
        ->assertSee('Laptop Pro');
})->with(['dataTable', 'data-table']);

it('renders the table with the x-data-table component', function () {
    $html = Blade::render(
        <<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Laptop Pro']])" preset="empty">
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE,
        deleteCachedView: true,
    );

    expect($html)->toContain('Laptop Pro');
});
