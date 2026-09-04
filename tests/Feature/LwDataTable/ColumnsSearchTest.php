<?php

use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Livewire\Livewire;

it('removes empty columnsSearch entry after nested update', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('columnsSearch.cpf', '');

    expect($component->get('columnsSearch'))->toBe([]);
});

it('removes null columnsSearch entry after nested update', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('columnsSearch.cpf', null);

    expect($component->get('columnsSearch'))->toBe([]);
});

it('keeps non-empty columnsSearch entry after nested update', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('columnsSearch.name', 'john');

    expect($component->get('columnsSearch'))->toBe(['name' => 'john']);
});

it('removes a columnsSearch entry when it is cleared back to empty', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('columnsSearch.name', 'john')
        ->set('columnsSearch.name', '');

    expect($component->get('columnsSearch'))->toBe([]);
});

it('only removes the columnsSearch entry that was cleared, keeping the others', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('columnsSearch.name', 'john')
        ->set('columnsSearch.email', 'x@example.com')
        ->set('columnsSearch.name', '');

    expect($component->get('columnsSearch'))->toBe(['email' => 'x@example.com']);
});
