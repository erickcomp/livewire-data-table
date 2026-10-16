<?php

// The demo table's rows have a dropdown with "View" (the product page), "Delete" (a handler, with confirmation) and
// "Discard" (a DELETE form, with confirmation), and a click on a row views it. Product #1 is "Product 038".
// Selectors must contain a CSS special character to be taken as CSS (see SmokeTest.php). Playwright dismisses the
// browser's dialogs, so the tests replace window.confirm.

const FIRST_ROW = 'tbody > tr:first-child';

function firstRowMenuItem(string $label): string
{
    return FIRST_ROW . ' [role="menuitem"]:text-is("' . $label . '")';
}

function openFirstRowActions(mixed $page): mixed
{
    return $page->click(FIRST_ROW . ' [aria-haspopup="menu"]');
}

it('leads to the page of the action picked in the dropdown', function () {
    $page = visit('/demo')
        ->assertDontSee('View');

    openFirstRowActions($page)
        ->assertSee('View')
        ->click(firstRowMenuItem('View'))
        ->assertSee('Product page: Product 038')
        ->assertNoJavaScriptErrors();
});

it('runs an action on the server once confirmed', function () {
    $page = visit('/demo');

    $page->script('window.confirm = (message) => { window.__confirmed = message; return true; }');

    openFirstRowActions($page)
        ->click(firstRowMenuItem('Delete'))
        ->waitForText('Product 038 deleted')
        ->assertScript('window.__confirmed', 'Delete Product 038?')
        ->assertSee('119 records found')
        ->assertSeeIn(FIRST_ROW . ' > td:nth-child(2)', '2')
        ->assertNoJavaScriptErrors();
});

it('does nothing when the confirmation is declined', function () {
    $page = visit('/demo');

    $page->script('window.confirm = () => false');

    openFirstRowActions($page)
        ->click(firstRowMenuItem('Delete'))
        ->wait(0.5)
        ->assertSee('120 records found')
        ->assertSeeIn(FIRST_ROW . ' > td:nth-child(2)', '1')
        ->assertNoJavaScriptErrors();
});

it('submits the form of an action with another method than GET', function () {
    $page = visit('/demo');

    $page->script('window.confirm = () => true');

    openFirstRowActions($page)
        ->click(firstRowMenuItem('Discard'))
        ->assertSee('119 records found')
        ->assertSeeIn(FIRST_ROW . ' > td:nth-child(2)', '2')
        ->assertNoJavaScriptErrors();
});

it('views a row when it is clicked', function () {
    visit('/demo')
        ->click(FIRST_ROW . ' > td:nth-child(3)')
        ->assertSee('Product page: Product 038')
        ->assertNoJavaScriptErrors();
});

it('does not take a click on a checkbox for a click on the row', function () {
    visit('/demo')
        ->click(FIRST_ROW . ' > td:first-child > input[type="checkbox"]')
        ->assertChecked(FIRST_ROW . ' > td:first-child > input[type="checkbox"]')
        ->assertSee('(1 selected)')
        ->assertDontSee('Product page')
        ->assertNoJavaScriptErrors();
});
