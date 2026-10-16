<?php

// The demo table edits Name (text, required), Category (select) and Price (number) inline: with "Edit" among the
// row's actions, or with a click on the row (?edit-on-click=1). Product #1 is "Product 038", in "Electronics".
// Selectors must contain a CSS special character to be taken as CSS (see SmokeTest.php).

const INLINE_EDIT_FIRST_ROW = 'tbody > tr:first-child';
const INLINE_EDIT_NAME_INPUT = 'tbody > tr:first-child input[aria-label="Name"]';

function startInlineEditFromMenu(mixed $page): mixed
{
    return $page->click(INLINE_EDIT_FIRST_ROW . ' [aria-haspopup="menu"]')
        ->click(INLINE_EDIT_FIRST_ROW . ' [role="menuitem"]:text-is("Edit")');
}

it('edits a row and saves it with Enter', function () {
    $page = visit('/demo')
        ->assertSeeIn(INLINE_EDIT_FIRST_ROW . ' > td:nth-child(3)', 'Product 038');

    startInlineEditFromMenu($page)
        ->assertValue(INLINE_EDIT_NAME_INPUT, 'Product 038')
        ->assertValue(INLINE_EDIT_FIRST_ROW . ' select[aria-label="Category"]', 'Electronics')
        // The first input takes the focus
        ->assertScript("document.activeElement.getAttribute('aria-label')", 'Name')
        ->type(INLINE_EDIT_NAME_INPUT, 'Product 038 (refurbished)')
        ->select(INLINE_EDIT_FIRST_ROW . ' select[aria-label="Category"]', 'Toys')
        ->keys(INLINE_EDIT_NAME_INPUT, 'Enter')
        ->assertSee('Product 038 (refurbished) saved')
        ->assertNotPresent(INLINE_EDIT_NAME_INPUT)
        ->assertSeeIn(INLINE_EDIT_FIRST_ROW . ' > td:nth-child(3)', 'Product 038 (refurbished)')
        ->assertSeeIn(INLINE_EDIT_FIRST_ROW . ' > td:nth-child(4)', 'Toys')
        ->assertNoJavaScriptErrors();
});

it('shows what is wrong, and drops the changes with Escape', function () {
    $page = visit('/demo')
        ->assertSee('120 records found');

    startInlineEditFromMenu($page)
        ->type(INLINE_EDIT_NAME_INPUT, '')
        ->click(INLINE_EDIT_FIRST_ROW . ' button:text-is("Save")')
        ->assertSee('The Name field is required.')
        ->assertAttribute(INLINE_EDIT_NAME_INPUT, 'aria-invalid', 'true')
        ->keys(INLINE_EDIT_FIRST_ROW . ' input[aria-label="Price"]', 'Escape')
        ->assertNotPresent(INLINE_EDIT_NAME_INPUT)
        ->assertDontSee('The Name field is required.')
        ->assertSeeIn(INLINE_EDIT_FIRST_ROW . ' > td:nth-child(3)', 'Product 038')
        ->assertNoJavaScriptErrors();
});

it('edits a row when it is clicked', function () {
    visit('/demo?edit-on-click=1')
        ->click(INLINE_EDIT_FIRST_ROW . ' > td:nth-child(2)')
        ->assertValue(INLINE_EDIT_FIRST_ROW . ' input[aria-label="Price"]', '17.4')
        // A click on the row being edited does not leave it
        ->click(INLINE_EDIT_FIRST_ROW . ' > td:nth-child(2)')
        ->type(INLINE_EDIT_FIRST_ROW . ' input[aria-label="Price"]', '19.9')
        ->click(INLINE_EDIT_FIRST_ROW . ' button:text-is("Save")')
        ->assertSee('Product 038 saved')
        ->assertSeeIn(INLINE_EDIT_FIRST_ROW . ' > td:nth-child(5)', '19.9')
        ->assertDontSee('Product page')
        ->assertNoJavaScriptErrors();
});
