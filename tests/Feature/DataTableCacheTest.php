<?php

use ErickComp\LivewireDataTable\DataTable;

it('produces the same cache key regardless of row-level code', function () {
    $dt1 = new DataTable(preset: 'empty');
    $dt2 = new DataTable(preset: 'empty');

    $reflection = new ReflectionClass(DataTable::class);
    $prop = $reflection->getProperty('rowLevelClassCode');
    $prop->setValue($dt2, '["bg-red" => true]');

    $key1 = $reflection->getMethod('cacheBaseFilename')->invoke($dt1);
    $key2 = $reflection->getMethod('cacheBaseFilename')->invoke($dt2);

    expect($key1)->toBe($key2);
});

it('restores row-level properties after computing cache key', function () {
    $dt = new DataTable(preset: 'empty');

    $reflection = new ReflectionClass(DataTable::class);
    $prop = $reflection->getProperty('rowLevelClassCode');
    $prop->setValue($dt, '["highlight" => $__row->active]');

    $reflection->getMethod('cacheBaseFilename')->invoke($dt);

    expect($prop->getValue($dt))->toBe('["highlight" => $__row->active]');
});
