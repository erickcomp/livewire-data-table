<?php

use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\DataColumn;

it('sets searchable to true and default mode when searchable is bool true', function () {
    $col = new DataColumn('Name', 'name', searchable: true);

    expect($col->isSearchable())->toBeTrue()
        ->and($col->searchMode)->toBe(Column::SEARCH_MODE_DEFAULT);
});

it('sets searchable to false when searchable is bool false', function () {
    $col = new DataColumn('Name', 'name', searchable: false);

    expect($col->isSearchable())->toBeFalse();
});

it('parses string "true" as boolean true', function () {
    $col = new DataColumn('Name', 'name', searchable: 'true');

    expect($col->isSearchable())->toBeTrue()
        ->and($col->searchMode)->toBe(Column::SEARCH_MODE_DEFAULT);
});

it('parses string "false" as boolean false', function () {
    $col = new DataColumn('Name', 'name', searchable: 'false');

    expect($col->isSearchable())->toBeFalse();
});

it('sets search mode from string value', function () {
    $col = new DataColumn('Name', 'name', searchable: 'starts_with');

    expect($col->isSearchable())->toBeTrue()
        ->and($col->searchMode)->toBe(Column::SEARCH_MODE_STARTS_WITH);
});

it('normalizes search mode to lowercase', function () {
    $col = new DataColumn('Name', 'name', searchable: 'ENDS_WITH');

    expect($col->isSearchable())->toBeTrue()
        ->and($col->searchMode)->toBe(Column::SEARCH_MODE_ENDS_WITH);
});

it('throws on invalid search mode string', function () {
    new DataColumn('Name', 'name', searchable: 'banana');
})->throws(\ValueError::class);

it('requires data-field for searchable columns', function () {
    new Column('Name', dataField: null, searchable: true);
})->throws(\BadMethodCallException::class);

it('requires data-field for sortable columns', function () {
    new Column('Name', dataField: null, sortable: true);
})->throws(\BadMethodCallException::class);
