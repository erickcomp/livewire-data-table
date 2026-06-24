<?php

use ErickComp\LivewireDataTable\ServiceProvider;

it('registers config for publishing', function () {
    $publishable = ServiceProvider::pathsToPublish(ServiceProvider::class, 'erickcomp-livewire-data-table-config');

    expect($publishable)->toBeArray()
        ->and($publishable)->not->toBeEmpty()
        ->and(array_values($publishable))->toContain(config_path('erickcomp-livewire-data-table.php'));
});

it('registers translations for publishing', function () {
    $publishable = ServiceProvider::pathsToPublish(ServiceProvider::class, 'erickcomp-livewire-data-table-lang');

    expect($publishable)->toBeArray()
        ->and($publishable)->not->toBeEmpty()
        ->and(array_values($publishable))->toContain(app()->langPath('vendor/erickcomp_lw_data_table'));
});

it('merges package config into application config', function () {
    $config = config('erickcomp-livewire-data-table');

    expect($config)->toBeArray()
        ->and($config)->toHaveKey('presets')
        ->and($config['presets'])->toHaveKey('empty')
        ->and($config['presets'])->toHaveKey('vanilla');
});
