<?php

// The demo page takes its per-page options from ?per-page-options= (15,30,60 by default) and remembers its state with
// ?remember=1. Selectors must contain a CSS special character to be taken as CSS (see SmokeTest.php).

const PER_PAGE_SELECT = 'select[wire\\:model\\.live="perPage"]';

function urlParam(string $name): string
{
    return "new URLSearchParams(window.location.search).get('$name')";
}

it('shows the rows per page of the first option', function () {
    // The select used to show "10" while the table showed 15 rows
    visit('/demo?per-page-options=10,25,50')
        ->assertValue(PER_PAGE_SELECT, '10')
        ->assertScript("document.querySelectorAll('tbody > tr').length", 10)
        ->assertSee('Showing 1 to 10 of 120 results')
        ->assertScript(urlParam('per-page'), null)
        ->assertNoJavaScriptErrors();
});

it('keeps the rows per page in the URL', function () {
    $page = visit('/demo')
        ->select(PER_PAGE_SELECT, '30')
        ->assertSee('Showing 1 to 30 of 120 results')
        ->assertScript(urlParam('per-page'), '30');

    $page->select(PER_PAGE_SELECT, '15')
        ->assertSee('Showing 1 to 15 of 120 results')
        ->assertScript(urlParam('per-page'), null);

    visit('/demo?per-page=60')
        ->assertValue(PER_PAGE_SELECT, '60')
        ->assertSee('Showing 1 to 60 of 120 results')
        ->assertNoJavaScriptErrors();
});

it('ignores rows per page that are not an option', function () {
    visit('/demo?per-page=5000')
        ->assertValue(PER_PAGE_SELECT, '15')
        ->assertSee('Showing 1 to 15 of 120 results')
        ->assertNoJavaScriptErrors();
});

it('brings the remembered state back, in the URL too', function () {
    visit('/demo?remember=1')
        ->type('.lw-dt-search-input', 'Product 04')
        ->press('.lw-dt-search-button')
        ->assertSee('10 records found')
        ->select(PER_PAGE_SELECT, '30')
        ->assertScript(urlParam('per-page'), '30');

    visit('/demo?remember=1')
        ->assertSee('10 records found')
        ->assertValue('.lw-dt-search-input', 'Product 04')
        ->assertValue(PER_PAGE_SELECT, '30')
        ->assertScript(urlParam('search'), 'Product 04')
        ->assertScript(urlParam('per-page'), '30')
        // Back to the default: out of the URL, as if the value had never been restored
        ->select(PER_PAGE_SELECT, '15')
        // Nothing changes on screen (the 10 rows fit either way): give the request time to finish
        ->wait(0.5)
        ->assertScript(urlParam('per-page'), null)
        ->assertScript(urlParam('search'), 'Product 04')
        ->assertNoJavaScriptErrors();

    // A URL with the table's parameters wins over the remembered state
    visit('/demo?remember=1&search=Product+05')
        ->assertSee('10 records found')
        ->assertValue('.lw-dt-search-input', 'Product 05')
        ->assertValue(PER_PAGE_SELECT, '15');
});

it('remembers nothing without remember-state', function () {
    visit('/demo')
        ->type('.lw-dt-search-input', 'Product 04')
        ->press('.lw-dt-search-button')
        ->assertSee('10 records found');

    visit('/demo')
        ->assertSee('120 records found')
        ->assertValue('.lw-dt-search-input', '');
});
