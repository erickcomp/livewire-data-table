<?php

use ErickComp\LivewireDataTable\DataTable\DataColumn;
use Tests\Fixtures\TestProduct;

it('returns the value of a field that is present', function () {
    $column = new DataColumn('Name', 'name');

    expect($column->getCellData(['name' => 'Widget'], 1))->toBe('Widget')
        ->and($column->getCellData((object) ['name' => 'Widget'], 1))->toBe('Widget')
        ->and($column->getCellData((new TestProduct())->forceFill(['name' => 'Widget']), 1))->toBe('Widget');
});

it('returns null for an Eloquent attribute that exists and holds null', function () {
    // data_get() walks objects with isset(), which is false for a null attribute: this used to be
    // reported as a missing field and abort the rendering of the whole table.
    $product = (new TestProduct())->forceFill(['name' => 'Widget', 'discontinued_at' => null]);

    expect((new DataColumn('Discontinued at', 'discontinued_at'))->getCellData($product, 1))->toBeNull();
});

it('returns null for a null property, array key or path segment', function () {
    expect((new DataColumn('Note', 'note'))->getCellData((object) ['note' => null], 1))->toBeNull()
        ->and((new DataColumn('Note', 'note'))->getCellData(['note' => null], 1))->toBeNull()
        ->and((new DataColumn('Category', 'category.name'))->getCellData(['category' => null], 1))->toBeNull();
});

it('returns null for a loaded relation that is not set', function () {
    $product = (new TestProduct())->forceFill(['name' => 'Widget']);
    $product->setRelation('category', null);

    expect((new DataColumn('Category', 'category.name'))->getCellData($product, 1))->toBeNull();
});

it('still throws for a field the row does not have at all', function (mixed $row) {
    expect(fn() => (new DataColumn('Missing', 'missing_field'))->getCellData($row, 3))
        ->toThrow(\LogicException::class, 'Cannot get data for column [missing_field] on row #3');
})->with([
    'array' => [['name' => 'Widget']],
    'object' => [(object) ['name' => 'Widget']],
    'eloquent model' => [(new TestProduct())->forceFill(['name' => 'Widget'])],
]);
