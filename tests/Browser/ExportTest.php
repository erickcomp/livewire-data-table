<?php

// The demo table exports CSV, XLSX and ODS files, named "products-{date}", from a menu. Livewire sends a file by
// clicking a hidden link to a blob: the tests catch that click and put the file's name and text in a <pre>.
// Selectors must contain a CSS special character to be taken as CSS (see SmokeTest.php).

const EXPORT_MENU_BUTTON = '.lw-dt-export-menu-container > button[aria-haspopup="menu"]';
const DOWNLOADED_FILE = 'pre#downloaded-file';

function exportMenuItem(string $label): string
{
    return '.lw-dt-export-menu-container [role="menuitem"]:text-is("' . $label . '")';
}

function catchDownloads(mixed $page): mixed
{
    $page->script(<<<'JS'
        () => {
            const createObjectURL = URL.createObjectURL.bind(URL);
            const click = HTMLAnchorElement.prototype.click;
            let lastBlob = null;

            URL.createObjectURL = (blob) => {
                lastBlob = blob;

                return createObjectURL(blob);
            };

            HTMLAnchorElement.prototype.click = function () {
                if (!this.download) {
                    return click.call(this);
                }

                const name = this.download;

                lastBlob.text().then((text) => {
                    const pre = document.createElement('pre');
                    pre.id = 'downloaded-file';
                    pre.dataset.name = name;
                    pre.textContent = text;
                    document.body.appendChild(pre);
                });
            };
        }
        JS);

    return $page;
}

/**
 * The text of the export button, without the hidden one
 */
function exportButtonTextScript(): string
{
    return "document.querySelector('" . EXPORT_MENU_BUTTON . "').innerText.replace('▾', '').trim()";
}

/**
 * The lines of the downloaded CSV file, without its BOM
 */
function downloadedCsvLinesScript(): string
{
    return "document.querySelector('" . DOWNLOADED_FILE . "').textContent.replace(/^\\uFEFF/, '').trim().split('\\n')";
}

it('downloads the rows matching the search, from every page', function () {
    $page = visit('/demo')
        ->type('.lw-dt-search-input', 'Product 04')
        ->press('.lw-dt-search-button')
        ->assertSee('10 records found');

    catchDownloads($page)
        ->click(EXPORT_MENU_BUTTON)
        ->click(exportMenuItem('CSV'))
        ->assertPresent(DOWNLOADED_FILE)
        ->assertAttribute(DOWNLOADED_FILE, 'data-name', 'products-' . now()->format('Y-m-d') . '.csv')
        ->assertScript(downloadedCsvLinesScript() . "[0] === 'ID,Name,Category,Price'")
        ->assertScript(downloadedCsvLinesScript() . '.length', 11)
        ->assertScript(downloadedCsvLinesScript() . ".slice(1).every(line => line.includes('Product 04'))")
        ->assertNoJavaScriptErrors();
});

it('downloads only the selected rows, and says so', function () {
    $page = visit('/demo')
        ->assertScript(exportButtonTextScript(), 'Export')
        ->click('tbody > tr:nth-child(1) > td:first-child > input[type="checkbox"]')
        ->click('tbody > tr:nth-child(3) > td:first-child > input[type="checkbox"]')
        ->assertSee('(2 selected)')
        ->assertScript(exportButtonTextScript(), 'Export selected');

    catchDownloads($page)
        ->click(EXPORT_MENU_BUTTON)
        ->click(exportMenuItem('CSV'))
        ->assertPresent(DOWNLOADED_FILE)
        ->assertScript(downloadedCsvLinesScript() . '.length', 3)
        ->assertScript(downloadedCsvLinesScript() . "[1].startsWith('1,')")
        ->assertScript(downloadedCsvLinesScript() . "[2].startsWith('3,')")
        // The rows stay selected
        ->assertSee('(2 selected)')
        ->assertNoJavaScriptErrors();
});

it('downloads spreadsheets', function () {
    $page = visit('/demo')
        ->assertSee('120 records found');

    catchDownloads($page)
        ->click(EXPORT_MENU_BUTTON)
        ->assertVisible(exportMenuItem('OpenDocument (.ods)'))
        ->click(exportMenuItem('Excel (.xlsx)'))
        ->assertPresent(DOWNLOADED_FILE)
        ->assertAttribute(DOWNLOADED_FILE, 'data-name', 'products-' . now()->format('Y-m-d') . '.xlsx')
        // XLSX files are zip files
        ->assertScript("document.querySelector('" . DOWNLOADED_FILE . "').textContent.startsWith('PK')")
        ->assertNoJavaScriptErrors();
});
