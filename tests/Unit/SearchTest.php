<?php

use ErickComp\LivewireDataTable\DataTable\Search;
use Illuminate\View\ComponentAttributeBag;

// --- String parsing ---

it('parses data-fields string with field:mode syntax', function () {
    $search = new Search(new ComponentAttributeBag([
        'data-fields' => 'name:starts_with,category:exact',
    ]));

    expect($search->dataFields)->toBe([
        'name' => 'starts_with',
        'category' => 'exact',
    ]);
});

it('defaults mode to contains when not specified in string', function () {
    $search = new Search(new ComponentAttributeBag([
        'data-fields' => 'name,category',
    ]));

    expect($search->dataFields)->toBe([
        'name' => 'contains',
        'category' => 'contains',
    ]);
});

// --- Boolean handling ---

it('sets data-fields to true when attribute is boolean true', function () {
    $search = new Search(new ComponentAttributeBag([
        'data-fields' => true,
    ]));

    expect($search->dataFields)->toBeTrue();
});

it('sets data-fields to true when attribute is string "true"', function () {
    $search = new Search(new ComponentAttributeBag([
        'data-fields' => 'true',
    ]));

    expect($search->dataFields)->toBeTrue();
});

// --- Array parsing ---

it('parses data-fields array with associative modes', function () {
    $search = new Search(new ComponentAttributeBag([
        'data-fields' => ['name' => 'starts_with', 'email' => 'exact'],
    ]));

    expect($search->dataFields)->toBe([
        'name' => 'starts_with',
        'email' => 'exact',
    ]);
});

it('parses data-fields array with numeric keys and field:mode syntax', function () {
    $search = new Search(new ComponentAttributeBag([
        'data-fields' => ['name:exact', 'category'],
    ]));

    expect($search->dataFields)->toBe([
        'name' => 'exact',
        'category' => 'contains',
    ]);
});

// --- Defaults ---

it('defaults data-fields to true when attribute is not set', function () {
    $search = new Search(new ComponentAttributeBag([]));

    expect($search->dataFields)->toBeTrue();
});

// --- Validation ---

it('throws on invalid mode in data-fields string', function () {
    new Search(new ComponentAttributeBag([
        'data-fields' => 'name:banana',
    ]));
})->throws(\ValueError::class);

it('throws on invalid mode in data-fields array', function () {
    new Search(new ComponentAttributeBag([
        'data-fields' => ['name' => 'banana'],
    ]));
})->throws(\ValueError::class);
