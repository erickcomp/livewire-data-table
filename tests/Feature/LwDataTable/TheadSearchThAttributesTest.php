<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;

function searchThDataTable(array $columns, ?ComponentAttributeBag $theadSearchThAttributes = null): DataTable
{
    $dataTable = new DataTable(
        dataSrc: collect([['name' => 'Alice', 'code' => 'A01']]),
        preset: 'empty',
        perPage: [],
        paginationView: 'bootstrap',
    );

    if ($theadSearchThAttributes !== null) {
        $dataTable->theadSearchThAttributes = $theadSearchThAttributes;
    }

    foreach ($columns as $col) {
        $dataTable->columns->push($col);
    }

    return $dataTable;
}

function searchThColumn(string $title, string $dataField, array $extraAttrs = []): DataColumn
{
    return DataColumn::fromComponentAttributeBag(new ComponentAttributeBag(array_merge([
        'title' => $title,
        'data-field' => $dataField,
        'searchable' => true,
    ], $extraAttrs)));
}

// --- DataTable-level theadSearchThAttributes ---

it('applies class from DataTable-level theadSearchThAttributes to all search row th elements', function () {
    $col = searchThColumn('Name', 'name');
    $dataTable = searchThDataTable([$col], new ComponentAttributeBag(['class' => 'test-dt-search-th-class']));

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSeeHtml('test-dt-search-th-class');
});

it('applies a non-class attribute from DataTable-level theadSearchThAttributes to all search row th elements', function () {
    $col = searchThColumn('Name', 'name');
    $dataTable = searchThDataTable([$col], new ComponentAttributeBag(['data-dt-srch' => 'yes']));

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSeeHtml('data-dt-srch="yes"');
});

// --- Column-level: thead-search-th-class (user's intuitive usage, identical prefix to DataTable-level) ---

it('does not leak thead-search-th-class as a literal attribute on the regular header th when set on a column', function () {
    $col = searchThColumn('Name', 'name', ['thead-search-th-class' => 'test-col-srch-th-a']);
    $dataTable = searchThDataTable([$col]);

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertDontSeeHtml('thead-search-th-class=');
});

it('applies thead-search-th-class from a column to that column\'s search row th', function () {
    $col = searchThColumn('Name', 'name', ['thead-search-th-class' => 'test-col-srch-th-a']);
    $dataTable = searchThDataTable([$col]);

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSeeHtml('test-col-srch-th-a');
});

// --- Column-level: th-search-th-class (canonical new prefix for per-column search th) ---

it('does not leak th-search-th-class as a literal attribute on the regular header th when set on a column', function () {
    $col = searchThColumn('Name', 'name', ['th-search-th-class' => 'test-col-srch-th-b']);
    $dataTable = searchThDataTable([$col]);

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    // The original attr name must not appear
    $component->assertDontSeeHtml('th-search-th-class=');
    // Nor should the stripped form "search-th-class=" appear (from going into thAttributes via th- prefix)
    $component->assertDontSeeHtml('search-th-class=');
});

it('applies th-search-th-class from a column to that column\'s search row th', function () {
    $col = searchThColumn('Name', 'name', ['th-search-th-class' => 'test-col-srch-th-b']);
    $dataTable = searchThDataTable([$col]);

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $component->assertSeeHtml('test-col-srch-th-b');
});

// --- Column-level attributes do not affect other columns' search th ---

it('applies th-search-th-class only to the correct column search th, not to all columns', function () {
    $colWithAttr = searchThColumn('Name', 'name', ['th-search-th-class' => 'test-col-srch-th-specific']);
    $colWithout   = searchThColumn('Code', 'code');
    $dataTable = searchThDataTable([$colWithAttr, $colWithout]);

    $html = Livewire::test(LwDataTable::class, ['data-table' => $dataTable])->html();

    // The class must appear exactly once (only for the 'name' column search th)
    expect(substr_count($html, 'test-col-srch-th-specific'))->toBe(1);
});
