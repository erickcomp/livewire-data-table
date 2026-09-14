<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;

/**
 * The column search input keeps its value in local Alpine state and only talks to Livewire on real
 * input. With wire:model, an Alpine plugin that writes to the model while the input mounts fired a
 * request every time the page opened: x-mask compares the model with the input value and only skips the
 * write when the model is null, but a columnsSearch key that doesn't exist yet reads as undefined — so it
 * wrote '' to it, and wire:model.live sent that to the server (the table loaded twice and the page was
 * reset to 1).
 */
function columnsSearchInputDataTable(array $columnAttrs = [], ?int $debounce = null): DataTable
{
    $dataTable = new DataTable(
        dataSrc: collect([['id' => 1, 'cpf' => '12345678901']]),
        preset: 'empty',
        perPage: [],
        paginationView: 'bootstrap',
        columnsSearchDebounce: $debounce,
    );

    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag(array_merge([
        'title' => 'CPF',
        'data-field' => 'cpf',
        'searchable' => true,
    ], $columnAttrs))));

    return $dataTable;
}

function columnsSearchInputHtml(string $html, string $dataField): string
{
    preg_match('/<input\b[^>]*columnsSearch\.' . preg_quote($dataField, '/') . '\b[^>]*>/s', $html, $match);

    return $match[0] ?? '';
}

it('binds the column search input to local Alpine state instead of wire:model', function () {
    $html = Livewire::test(LwDataTable::class, ['data-table' => columnsSearchInputDataTable()])->html();
    $input = columnsSearchInputHtml($html, 'cpf');

    expect($input)->toContain('x-model="value"')
        ->and($input)->toContain("\$wire.\$set('columnsSearch.cpf', value)")
        ->and($input)->not->toContain('wire:model');
});

it('keeps th-search-input attributes such as x-mask on the column search input', function () {
    $dataTable = columnsSearchInputDataTable(['th-search-input-x-mask' => '999.999.999-99']);
    $input = columnsSearchInputHtml(Livewire::test(LwDataTable::class, ['data-table' => $dataTable])->html(), 'cpf');

    expect($input)->toContain('x-mask="999.999.999-99"')
        ->and($input)->not->toContain('wire:model');
});

it('starts the local state empty when there is no column search yet', function () {
    $html = Livewire::test(LwDataTable::class, ['data-table' => columnsSearchInputDataTable()])->html();

    expect(columnsSearchInputHtml($html, 'cpf'))->toContain("x-data=\"{ value: '' }\"");
});

it('seeds the local state with the current column search value', function () {
    $html = Livewire::test(LwDataTable::class, ['data-table' => columnsSearchInputDataTable()])
        ->set('columnsSearch.cpf', '123')
        ->html();

    expect(columnsSearchInputHtml($html, 'cpf'))->toContain("x-data=\"{ value: '123' }\"");
});

it('honors the data table columns search debounce on the column search input', function () {
    $dataTable = columnsSearchInputDataTable(debounce: 777);
    $input = columnsSearchInputHtml(Livewire::test(LwDataTable::class, ['data-table' => $dataTable])->html(), 'cpf');

    expect($input)->toContain('x-on:input.debounce.777ms=');
});
