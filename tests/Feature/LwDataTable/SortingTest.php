<?php

use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Livewire\Livewire;

it('toggles sort direction and resets sortBy when toggled to none', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('sortBy', 'name')
        ->set('sortDir', 'ASC');

    $component->call('setSortBy', 'name');
    expect($component->get('sortDir'))->toBe('DESC');

    $component->call('setSortBy', 'name');
    expect($component->get('sortDir'))->toBe('')
        ->and($component->get('sortBy'))->toBe('');
});

it('sets sort direction to ASC when changing to a different field', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('sortBy', 'name')
        ->set('sortDir', 'DESC');

    $component->call('setSortBy', 'email');

    expect($component->get('sortBy'))->toBe('email')
        ->and($component->get('sortDir'))->toBe('ASC');
});

it('allows sorting only when more than one row exists', function () {
    $component = Livewire::test(LwDataTable::class);

    expect($component->instance()->shouldAllowSorting(collect([1, 2])))->toBeTrue()
        ->and($component->instance()->shouldAllowSorting(collect([1])))->toBeFalse();
});
