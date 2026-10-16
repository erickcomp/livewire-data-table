<?php

// The demo page lists 120 products, 15 per page, with row selection on. Selectors must contain a CSS special
// character to be taken as CSS (see SmokeTest.php).

const ROW_CHECKBOX = 'tbody > tr:nth-child(%d) > td:first-child > input[type="checkbox"]';
const PAGE_CHECKBOX = 'thead > tr:first-child > th:first-child > input[type="checkbox"]';
const SELECTION_MENU_BUTTON = 'thead [aria-label="Selection options"]';

function rowCheckbox(int $row): string
{
    return \sprintf(ROW_CHECKBOX, $row);
}

function selectionMenuItem(string $text): string
{
    return '[role="menuitem"]:text-is("' . $text . '")';
}

it('counts the records and the rows checked one by one', function () {
    $page = visit('/demo')
        ->assertSee('120 records found')
        ->assertDontSee('selected');

    $page->click(rowCheckbox(1))
        ->assertChecked(rowCheckbox(1))
        ->assertSee('(1 selected)')
        ->click(rowCheckbox(3))
        ->assertSee('(2 selected)')
        ->assertIndeterminate(PAGE_CHECKBOX)
        ->click(rowCheckbox(1))
        ->assertNotChecked(rowCheckbox(1))
        ->assertSee('(1 selected)')
        ->assertNoJavaScriptErrors();
});

it('keeps the checked rows across pages', function () {
    $page = visit('/demo');

    $page->click(rowCheckbox(2))
        ->click('[wire\\:click^="gotoPage(2,"]')
        ->assertSeeIn('tbody > tr:first-child > td:nth-child(2)', '16')
        ->assertNotChecked(rowCheckbox(2))
        ->click(rowCheckbox(1))
        ->assertSee('(2 selected)')
        ->click('[wire\\:click^="gotoPage(1,"]')
        ->assertSeeIn('tbody > tr:first-child > td:nth-child(2)', '1')
        ->assertChecked(rowCheckbox(2))
        ->assertSee('(2 selected)')
        ->assertNoJavaScriptErrors();
});

it('checks and unchecks the whole page with the header checkbox', function () {
    $page = visit('/demo');

    $page->click(PAGE_CHECKBOX)
        ->assertChecked(PAGE_CHECKBOX)
        ->assertChecked(rowCheckbox(15))
        ->assertSee('(15 selected)')
        ->click(PAGE_CHECKBOX)
        ->assertNotChecked(PAGE_CHECKBOX)
        ->assertNotChecked(rowCheckbox(1))
        ->assertDontSee('selected)')
        ->assertNoJavaScriptErrors();
});

it('selects every matching row from the menu, leaving out the ones unchecked afterwards', function () {
    $page = visit('/demo');

    $page->click(SELECTION_MENU_BUTTON)
        ->click(selectionMenuItem('Select all (120)'))
        ->assertSee('(120 selected)')
        ->assertChecked(rowCheckbox(1))
        ->click(rowCheckbox(1))
        ->assertSee('(119 selected)')
        ->click('[wire\\:click^="gotoPage(2,"]')
        ->assertSeeIn('tbody > tr:first-child > td:nth-child(2)', '16')
        ->assertChecked(rowCheckbox(1))
        ->assertSee('(119 selected)')
        ->click(SELECTION_MENU_BUTTON)
        ->click(selectionMenuItem('Deselect all'))
        ->assertNotChecked(rowCheckbox(1))
        ->assertDontSee('selected)')
        ->assertNoJavaScriptErrors();
});

it('selects and deselects the page from the menu', function () {
    $page = visit('/demo');

    $page->click(SELECTION_MENU_BUTTON)
        ->click(selectionMenuItem('Select all on this page'))
        ->assertSee('(15 selected)')
        ->click(SELECTION_MENU_BUTTON)
        ->click(selectionMenuItem('Deselect all on this page'))
        ->assertDontSee('selected)')
        ->assertNoJavaScriptErrors();
});

it('drops the selection when the search changes', function () {
    $page = visit('/demo');

    $page->click(rowCheckbox(1))
        ->assertSee('(1 selected)')
        ->type('.lw-dt-search-input', 'Product 04')
        ->press('.lw-dt-search-button')
        ->assertSee('10 records found')
        ->assertDontSee('selected)')
        ->assertNoJavaScriptErrors();
});
