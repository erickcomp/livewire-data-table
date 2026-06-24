<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;

function makeColumn(string $title, string $dataField, bool $sortable = false, bool $searchable = false): DataColumn
{
    return DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => $title,
        'data-field' => $dataField,
        'sortable' => $sortable,
        'searchable' => $searchable,
    ]));
}

it('rejects sortBy values that do not match a sortable column', function () {
    $dataTable = new DataTable(dataSrc: collect([
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ]));

    $dataTable->columns->push(makeColumn('ID', 'id', sortable: true));
    $dataTable->columns->push(makeColumn('Name', 'name'));

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $sanitizeMethod = new ReflectionMethod($component->instance(), 'sanitizeSortBy');
    $result = $sanitizeMethod->invoke($component->instance(), 'malicious_column; DROP TABLE users--');

    expect($result)->toBe('');
});

it('allows sortBy values that match a declared sortable column', function () {
    $dataTable = new DataTable(dataSrc: collect([
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ]));

    $dataTable->columns->push(makeColumn('ID', 'id', sortable: true));
    $dataTable->columns->push(makeColumn('Name', 'name', sortable: true));

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $sanitizeMethod = new ReflectionMethod($component->instance(), 'sanitizeSortBy');
    $result = $sanitizeMethod->invoke($component->instance(), 'name');

    expect($result)->toBe('name');
});

it('rejects sortBy for columns that exist but are not sortable', function () {
    $dataTable = new DataTable(dataSrc: collect([
        ['id' => 1, 'name' => 'Alice'],
    ]));

    $dataTable->columns->push(makeColumn('ID', 'id'));

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $sanitizeMethod = new ReflectionMethod($component->instance(), 'sanitizeSortBy');
    $result = $sanitizeMethod->invoke($component->instance(), 'id');

    expect($result)->toBe('');
});

it('strips columnsSearch keys that do not match searchable columns', function () {
    $dataTable = new DataTable(dataSrc: collect([
        ['id' => 1, 'name' => 'Alice', 'email' => 'a@b.com'],
    ]));

    $dataTable->columns->push(makeColumn('ID', 'id'));
    $dataTable->columns->push(makeColumn('Name', 'name', searchable: true));
    $dataTable->columns->push(makeColumn('Email', 'email', searchable: true));

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $sanitizeMethod = new ReflectionMethod($component->instance(), 'sanitizeColumnsSearch');
    $result = $sanitizeMethod->invoke($component->instance(), [
        'name' => 'alice',
        'evil_column' => 'payload',
        'email' => 'test',
        'id' => 'not_searchable',
    ]);

    expect($result)->toBe([
        'name' => 'alice',
        'email' => 'test',
    ]);
});

it('returns empty array when no columnsSearch keys match', function () {
    $dataTable = new DataTable(dataSrc: collect([
        ['id' => 1],
    ]));

    $dataTable->columns->push(makeColumn('ID', 'id'));

    $component = Livewire::test(LwDataTable::class, ['data-table' => $dataTable]);

    $sanitizeMethod = new ReflectionMethod($component->instance(), 'sanitizeColumnsSearch');
    $result = $sanitizeMethod->invoke($component->instance(), [
        'nonexistent' => 'value',
    ]);

    expect($result)->toBe([]);
});
