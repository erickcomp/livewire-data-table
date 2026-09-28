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

it('returns null for a key set to null in the child instead of climbing to the parent', function () {
    config()->set('erickcomp-livewire-data-table.presets.null-child-test', [
        'extends' => 'vanilla',
        'table' => ['class' => null],
        'reload-alert' => null,
    ]);

    $preset = Preset::loadFromName('null-child-test');

    expect(Preset::loadFromName('vanilla')->get('table.class'))->toBe(['lw-dt-table']);

    // Twice each: the second call is answered from what the first one resolved
    expect($preset->get('table.class'))->toBeNull()
        ->and($preset->get('table.class', 'fallback'))->toBeNull()
        ->and($preset->get('reload-alert', 'fallback'))->toBeNull()
        ->and($preset->get('reload-alert', 'fallback'))->toBeNull();
});

it('applies the default given on each call to a missing key', function () {
    config()->set('erickcomp-livewire-data-table.presets.default-per-call-test', [
        'extends' => 'vanilla',
    ]);

    $preset = Preset::loadFromName('default-per-call-test');

    expect($preset->get('nonexistent.deep.key', 'first'))->toBe('first')
        ->and($preset->get('nonexistent.deep.key', 'second'))->toBe('second')
        ->and($preset->get('nonexistent.deep.key'))->toBeNull()
        ->and($preset->get('nonexistent.deep.key', []))->toBe([])
        ->and($preset->get('table.class', 'fallback'))->toBe(['lw-dt-table']);
});

it('keeps the serialized form of a preset after values are resolved', function () {
    $preset = new Preset('serialize-test', ['extends' => 'empty', 'a' => ['b' => 1, 'c' => null]]);

    // What PHP writes for the two protected properties: the file cache of DataTable (which holds its
    // preset) is named after the md5 of this form, so it must not change with what get() resolved
    $expected = 'O:43:"' . Preset::class . '":2:{'
        . 's:7:"' . "\0*\0" . 'name";s:14:"serialize-test";'
        . 's:13:"' . "\0*\0" . 'presetInfo";a:2:{s:7:"extends";s:5:"empty";s:1:"a";a:2:{s:1:"b";i:1;s:1:"c";N;}}'
        . '}';

    expect(\serialize($preset))->toBe($expected);

    $preset->get('a.b');
    $preset->get('a.c');
    $preset->get('a.missing', 'default');
    $preset->get('table.class');
    $preset->get('nonexistent.deep.key');
    $preset->parent;

    expect(\serialize($preset))->toBe($expected);
});

it('comes back from unserialize with nothing resolved and resolves again', function () {
    $preset = new Preset('unserialize-test', ['extends' => 'vanilla', 'a' => ['b' => 1, 'c' => null]]);

    $preset->get('a.b');
    $preset->get('a.c');
    $preset->get('table.class');
    $preset->get('nonexistent.deep.key');

    $restored = \unserialize(\serialize($preset));

    $reflection = new ReflectionObject($restored);
    $memoizedState = \array_filter(
        $reflection->getProperties(),
        fn (ReflectionProperty $prop) => !$prop->isStatic() && !\in_array($prop->getName(), ['name', 'presetInfo'], true),
    );

    foreach ($memoizedState as $prop) {
        expect($prop->getValue($restored))->toBeEmpty();
    }

    expect($restored)->toBeInstanceOf(Preset::class)
        ->and($restored->get())->toBe(['extends' => 'vanilla', 'a' => ['b' => 1, 'c' => null]])
        ->and($restored->get('a.b'))->toBe(1)
        ->and($restored->get('a.c', 'fallback'))->toBeNull()
        ->and($restored->get('table.class'))->toBe(['lw-dt-table'])
        ->and($restored->get('nonexistent.deep.key', 'fallback'))->toBe('fallback')
        ->and($restored->parent)->toBe(Preset::loadFromName('vanilla'));
});

it('keeps a data table serializable, with the same form, after its preset resolved missing keys', function () {
    config()->set('erickcomp-livewire-data-table.presets.data-table-serialize-test', [
        'extends' => 'vanilla',
    ]);

    $dataTable = new \ErickComp\LivewireDataTable\DataTable(preset: 'data-table-serialize-test');
    $dataTable->preset();

    $before = \serialize($dataTable);

    $dataTable->preset()->get('nonexistent.deep.key');
    $dataTable->preset()->get('table.class');
    $dataTable->preset()->parent->get('another.nonexistent.key');

    expect(\serialize($dataTable))->toBe($before);
});

it('has no parent when the preset does not declare extends', function () {
    config()->set('erickcomp-livewire-data-table.presets.no-extends-test', [
        'table' => ['class' => ['no-extends']],
    ]);

    $preset = Preset::loadFromName('no-extends-test');

    expect($preset->parent)->toBeNull()
        ->and($preset->get('table.class'))->toBe(['no-extends'])
        ->and($preset->get('nonexistent.deep.key', 'fallback'))->toBe('fallback');
});
