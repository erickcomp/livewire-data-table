<?php

use ErickComp\LivewireDataTable\Livewire\Preset;

it('loads a built-in preset by name', function () {
    $preset = Preset::loadFromName('vanilla');

    expect($preset)->toBeInstanceOf(Preset::class)
        ->and($preset->get())->toBeArray();
});

it('throws on invalid preset name', function () {
    Preset::loadFromName('nonexistent_preset_xyz');
})->throws(\InvalidArgumentException::class);

it('resolves values from parent preset via extends', function () {
    config()->set('erickcomp-livewire-data-table.presets.child-test', [
        'extends' => 'vanilla',
        'custom-key' => 'child-value',
    ]);

    $preset = Preset::loadFromName('child-test');

    expect($preset->get('custom-key'))->toBe('child-value')
        ->and($preset->get('table.class'))->not->toBeNull();
});

it('returns default when key is not found in preset or parent', function () {
    $preset = Preset::loadFromName('empty');

    expect($preset->get('nonexistent.deep.key', 'fallback'))->toBe('fallback');
});

it('caches preset instances', function () {
    $first = Preset::loadFromName('vanilla');
    $second = Preset::loadFromName('vanilla');

    expect($first)->toBe($second);
});
