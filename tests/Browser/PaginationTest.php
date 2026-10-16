<?php

// The demo page lists 120 products, 15 per page, with the pagination type taken from ?pagination=. Product #1 is
// "Product 038", #16 (first on page 2) is "Product 113", #31 (first on page 3) is "Product 068" and #120 is
// "Product 001".

const FIRST_ROW_NAME = 'tbody > tr:first-child > td:nth-child(3)';

it('pages with length-aware pagination by default', function () {
    visit('/demo')
        ->assertSeeIn(FIRST_ROW_NAME, 'Product 038')
        ->assertSee('120 records found')
        ->assertSee('Showing 1 to 15 of 120 results')
        ->click('[wire\\:click^="gotoPage(2,"]')
        ->assertSeeIn(FIRST_ROW_NAME, 'Product 113')
        ->assertNoJavaScriptErrors();
});

it('pages with simple pagination', function (string $pagination) {
    visit("/demo?pagination=$pagination")
        ->assertSeeIn(FIRST_ROW_NAME, 'Product 038')
        ->assertCount('tbody > tr', 15)
        ->assertDontSee('records found')
        ->assertDontSee('Showing')
        ->click('[dusk="nextPage"]')
        ->assertSeeIn(FIRST_ROW_NAME, 'Product 113')
        ->click('[dusk="nextPage"]')
        ->assertSeeIn(FIRST_ROW_NAME, 'Product 068')
        ->click('[dusk="previousPage"]')
        ->assertSeeIn(FIRST_ROW_NAME, 'Product 113')
        ->assertNoJavaScriptErrors();
})->with(['simple', 'cursor']);

it('lists every row without pagination', function () {
    visit('/demo?pagination=none')
        ->assertSeeIn('tbody > tr:last-child > td:nth-child(3)', 'Product 001')
        // Counted in the page: the plugin's assertCount() stops at 100
        ->assertScript("document.querySelectorAll('tbody > tr').length", 120)
        ->assertSee('120 records found')
        ->assertDontSee('Showing')
        ->assertNotPresent('[dusk="nextPage"]')
        ->assertNoJavaScriptErrors();
});
