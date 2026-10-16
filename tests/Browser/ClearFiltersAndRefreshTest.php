<?php

use Tests\Fixtures\TestProduct;

// The demo table has id="products". Selectors must contain a CSS special character to be taken as CSS (see
// SmokeTest.php).

const CATEGORY_FILTER = 'select[x-model*="category"]';

it('clears the search and the filters at once', function () {
    $page = visit('/demo?search=Product+0&filters[category][category]=Books');

    $page->assertSee('Clear all')
        ->assertDontSee('120 records found')
        ->assertValue('.lw-dt-search-input', 'Product 0')
        ->assertValue(CATEGORY_FILTER, 'Books')
        ->click('button:text-is("Clear all")')
        ->assertSee('120 records found')
        ->assertDontSee('Clear all')
        ->assertValue('.lw-dt-search-input', '')
        ->assertValue(CATEGORY_FILTER, '')
        ->assertNoJavaScriptErrors();
});

it('re-renders the table named by the refresh event', function () {
    $page = visit('/demo')
        ->assertSeeIn('tbody > tr:first-child', 'Product 038');

    TestProduct::query()->whereKey(1)->update(['name' => 'Renamed product']);

    $page->script("Livewire.dispatch('lw-dt-refresh', { table: 'products' })");

    $page->assertSeeIn('tbody > tr:first-child', 'Renamed product')
        ->assertNoJavaScriptErrors();
});

it('leaves the table alone on the refresh event of another table', function () {
    // visit() only loads the page on the first interaction or assertion: before the update, then
    $page = visit('/demo')
        ->assertSeeIn('tbody > tr:first-child', 'Product 038');

    TestProduct::query()->whereKey(1)->update(['name' => 'Renamed product']);

    $page->script("Livewire.dispatch('lw-dt-refresh', { table: 'users' })");

    $page->wait(0.5)
        ->assertSeeIn('tbody > tr:first-child', 'Product 038')
        ->assertNoJavaScriptErrors();
});
