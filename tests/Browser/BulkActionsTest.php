<?php

// The demo page lists 120 products, 15 per page, with a "Delete" bulk action (with confirmation) and a "Move to"
// one with a category per option. Product #1 is in "Electronics". Selectors must contain a CSS special character to
// be taken as CSS (see SmokeTest.php). Playwright dismisses the browser's dialogs, so the tests replace them.

const BULK_ACTIONS_SELECT = 'select.lw-dt-bulk-actions-select';
const BULK_ROW_CHECKBOX = 'tbody > tr:nth-child(%d) > td:first-child > input[type="checkbox"]';

function bulkRowCheckbox(int $row): string
{
    return \sprintf(BULK_ROW_CHECKBOX, $row);
}

it('runs an action on the checked rows once confirmed', function () {
    $page = visit('/demo');

    $page->script('window.confirm = (message) => { window.__confirmed = message; return true; }');

    $page->click(bulkRowCheckbox(1))
        ->click(bulkRowCheckbox(2))
        ->assertSee('(2 selected)')
        ->select(BULK_ACTIONS_SELECT, 'delete')
        ->waitForText('2 product(s) deleted')
        ->assertScript('window.__confirmed', 'Delete 2 product(s)?')
        ->assertSee('118 records found')
        ->assertDontSee('selected)')
        ->assertSeeIn('tbody > tr:first-child > td:nth-child(2)', '3')
        ->assertValue(BULK_ACTIONS_SELECT, '')
        ->assertNoJavaScriptErrors();
});

it('does nothing when the confirmation is declined', function () {
    $page = visit('/demo');

    $page->script('window.confirm = () => false');

    $page->click(bulkRowCheckbox(1))
        ->select(BULK_ACTIONS_SELECT, 'delete')
        ->assertValue(BULK_ACTIONS_SELECT, '')
        ->assertSee('120 records found')
        ->assertSee('(1 selected)')
        ->assertChecked(bulkRowCheckbox(1))
        ->assertNoJavaScriptErrors();
});

it('hands the option picked over to the action', function () {
    $page = visit('/demo');

    $page->click(bulkRowCheckbox(1))
        ->assertSeeIn('tbody > tr:first-child > td:nth-child(4)', 'Electronics')
        ->select(BULK_ACTIONS_SELECT, 'category:Toys')
        ->waitForText('1 product(s) moved to Toys')
        ->assertSeeIn('tbody > tr:first-child > td:nth-child(4)', 'Toys')
        ->assertNotChecked(bulkRowCheckbox(1))
        ->assertNoJavaScriptErrors();
});

it('runs the action on every matching row but the ones unchecked', function () {
    $page = visit('/demo');

    $page->script('window.confirm = () => true');

    $page->click('thead [aria-label="Selection options"]')
        ->click('[role="menuitem"]:text-is("Select all (120)")')
        ->assertSee('(120 selected)')
        ->click(bulkRowCheckbox(1))
        ->assertSee('(119 selected)')
        ->select(BULK_ACTIONS_SELECT, 'delete')
        ->waitForText('119 product(s) deleted')
        ->assertSee('1 record found')
        ->assertSeeIn('tbody > tr:first-child > td:nth-child(2)', '1')
        ->assertNoJavaScriptErrors();
});

it('asks to select rows first', function () {
    $page = visit('/demo');

    $page->script('window.alert = (message) => { window.__alerted = message; }');

    $page->select(BULK_ACTIONS_SELECT, 'delete')
        ->assertScript('window.__alerted', 'Select at least one record to run this action.')
        ->assertValue(BULK_ACTIONS_SELECT, '')
        ->assertSee('120 records found')
        ->assertNoJavaScriptErrors();
});

it('dismisses the message', function () {
    $page = visit('/demo');

    $page->click(bulkRowCheckbox(1))
        ->select(BULK_ACTIONS_SELECT, 'category:Books')
        ->waitForText('1 product(s) moved to Books')
        ->click('.lw-dt-message-dismiss')
        ->assertDontSee('1 product(s) moved to Books')
        ->assertNoJavaScriptErrors();
});
