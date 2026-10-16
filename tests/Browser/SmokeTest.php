<?php

// The demo page (workbench/resources/views/demo.blade.php) lists the 120 products seeded by
// Workbench\Database\Seeders\DatabaseSeeder, 15 per page, ordered by id: product #1 is "Product 038".
//
// Selectors must contain a CSS special character (">", ":", "[", ...) to be taken as CSS by the browser
// plugin; a bare "tbody tr" would be looked up as text.

it('renders the demo table without JavaScript errors', function () {
    visit('/demo')
        ->assertSeeIn('tbody > tr:first-child', 'Product 038')
        ->assertCount('tbody > tr', 15)
        ->assertNoJavaScriptErrors();
});

it('sorts the rows when a sortable header is clicked', function () {
    $page = visit('/demo');

    $page->click('th:has-text("Name")')
        ->assertSeeIn('tbody > tr:first-child', 'Product 001');

    $page->click('th:has-text("Name")')
        ->assertSeeIn('tbody > tr:first-child', 'Product 120');

    $page->assertNoJavaScriptErrors();
});

it('filters the rows with the global search', function () {
    visit('/demo')
        ->type('.lw-dt-search-input', 'Product 042')
        ->press('.lw-dt-search-button')
        ->assertSeeIn('tbody > tr:first-child', 'Product 042')
        ->assertCount('tbody > tr', 1)
        ->assertNoJavaScriptErrors();
});

it('navigates between pages', function () {
    visit('/demo')
        ->click('[wire\\:click^="gotoPage(2,"]')
        ->assertSeeIn('tbody > tr:first-child > td:nth-child(2)', '16')
        ->assertNoJavaScriptErrors();
});
