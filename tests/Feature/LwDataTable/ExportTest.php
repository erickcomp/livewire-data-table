<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\CustomRenderedColumn;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\Export\OpenSpoutWriter;
use ErickComp\LivewireDataTable\Export\PhpSpreadsheetWriter;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Fixtures\TestProduct;

uses(RefreshDatabase::class);

const EXPORT_CSV_CONTENT_TYPE = 'text/csv; charset=UTF-8';
const EXPORT_BOM = "\u{FEFF}";

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-16 14:30:05'));

    TestProduct::insert([
        ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'electronics', 'price' => 1299.99, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Wireless Mouse', 'category' => 'electronics', 'price' => 29.99, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'name' => 'Office Desk', 'category' => 'furniture', 'price' => 349.5, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 4, 'name' => 'Ergonomic Chair', 'category' => 'furniture', 'price' => 599.5, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 5, 'name' => 'Bookshelf', 'category' => 'furniture', 'price' => 129.5, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

/**
 * A table of products paged 2 by 2, with a search on the name, a filter on the category and an export
 *
 * @param array<string, mixed> $exportAttributes The attributes of the <x-data-table.export>
 * @param array<int, array<string, mixed>|array{0: array<string, mixed>, 1: string}> $columns The attributes of each
 *        column, with the Blade of a custom column after them
 */
function exportDataTable(array $exportAttributes = [], array $columns = [], array $options = []): DataTable
{
    $dataTable = new DataTable(
        dataSrc: $options['dataSrc'] ?? TestProduct::class,
        preset: $options['preset'] ?? 'empty',
        perPage: '2',
        selectable: $options['selectable'] ?? false,
    );

    $dataTable->search = new DataTable\Search(new ComponentAttributeBag(['data-fields' => 'name']));

    $columns = empty($columns)
        ? [['title' => 'ID', 'data-field' => 'id'], ['title' => 'Name', 'data-field' => 'name', 'sortable' => true], ['title' => 'Price', 'data-field' => 'price']]
        : $columns;

    foreach ($columns as $column) {
        if (isset($column[1])) {
            $dataTable->addCustomRenderedColumn(new ComponentAttributeBag($column[0]), $column[1]);
        } else {
            $dataTable->addDataColumn(new ComponentAttributeBag($column));
        }
    }

    $dataTable->addFilter(new ComponentAttributeBag(['data-field' => 'category', 'label' => 'Category', 'mode' => 'exact']));
    $dataTable->setExport(new ComponentAttributeBag(['formats' => 'csv,xlsx,ods', 'filename' => 'products-{date}', ...$exportAttributes]));

    return $dataTable;
}

function exportedFileContent(mixed $component): string
{
    return base64_decode(data_get($component->effects, 'download.content'));
}

/**
 * The rows of the CSV file the component sent, read as a spreadsheet reads them. The writers quote different values
 * (OpenSpout quotes text with spaces, like fputcsv(), and PhpSpreadsheet does not), which makes no difference there.
 *
 * @return array<int, array<int, string>>
 */
function exportedCsvRows(mixed $component): array
{
    $content = exportedFileContent($component);

    expect($content)->toStartWith(EXPORT_BOM);

    $stream = fopen('php://memory', 'r+');
    fwrite($stream, substr($content, strlen(EXPORT_BOM)));
    rewind($stream);

    $rows = [];

    while (($row = fgetcsv($stream, escape: '')) !== false) {
        $rows[] = $row;
    }

    fclose($stream);

    return $rows;
}

/**
 * Exports the table as CSV and returns the rows of the file
 *
 * @return array<int, array<int, string>>
 */
function exportCsvRows(mixed $component): array
{
    return exportedCsvRows($component->call('export', 'csv')->assertFileDownloaded('products-2026-10-16.csv', contentType: EXPORT_CSV_CONTENT_TYPE));
}

// --- Rows ---

it('downloads the rows of every page as CSV', function (string $writer, string $expected) {
    config()->set('erickcomp-livewire-data-table.export.writer', $writer);

    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable()])
        ->assertSee('Laptop Pro')
        ->assertDontSee('Office Desk')
        ->call('export', 'csv')
        ->assertFileDownloaded('products-2026-10-16.csv', EXPORT_BOM . $expected, EXPORT_CSV_CONTENT_TYPE);
})->with([
    // Both are the same CSV: fputcsv(), which OpenSpout uses, quotes text with spaces
    'openspout' => ['openspout', "ID,Name,Price\n1,\"Laptop Pro\",1299.99\n2,\"Wireless Mouse\",29.99\n3,\"Office Desk\",349.5\n4,\"Ergonomic Chair\",599.5\n5,Bookshelf,129.5\n"],
    'phpspreadsheet' => ['phpspreadsheet', "ID,Name,Price\n1,Laptop Pro,1299.99\n2,Wireless Mouse,29.99\n3,Office Desk,349.5\n4,Ergonomic Chair,599.5\n5,Bookshelf,129.5\n"],
]);

it('exports the rows matching the search and the filters, sorted like the table', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable()])
        ->call('applyFilters', ['category' => ['category' => 'furniture']])
        ->set('search', 'e')
        ->call('setSortBy', 'name');

    expect(exportCsvRows($component))->toBe([
        ['ID', 'Name', 'Price'],
        ['5', 'Bookshelf', '129.5'],
        ['4', 'Ergonomic Chair', '599.5'],
        ['3', 'Office Desk', '349.5'],
    ]);
});

it('exports only the selected rows, when there are any', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(options: ['selectable' => true])])
        ->call('setSortBy', 'name')
        ->set('selectedIds', ['4', '1', '999']);

    expect(exportCsvRows($component))->toBe([['ID', 'Name', 'Price'], ['4', 'Ergonomic Chair', '599.5'], ['1', 'Laptop Pro', '1299.99']]);

    $component->assertSet('selectedIds', ['4', '1', '999']);
});

it('exports all the matching rows but the ones left out', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(options: ['selectable' => true])])
        ->call('applyFilters', ['category' => ['category' => 'furniture']])
        ->call('selectAllMatching')
        ->set('selectedIds', ['4']);

    expect(exportCsvRows($component))->toBe([['ID', 'Name', 'Price'], ['3', 'Office Desk', '349.5'], ['5', 'Bookshelf', '129.5']]);
});

it('ignores ids sent to a table whose rows cannot be selected', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable()])
        ->set('selectedIds', ['4']);

    expect(exportCsvRows($component))->toHaveCount(6);
});

it('exports the header alone when no row matches', function () {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable()])
        ->set('search', 'nothing like this');

    expect(exportCsvRows($component))->toBe([['ID', 'Name', 'Price']]);
});

it('exports the rows of a collection', function () {
    $rows = collect([
        ['id' => 1, 'name' => 'Laptop Pro', 'price' => 1299.99],
        ['id' => 2, 'name' => 'Wireless Mouse', 'price' => 29.99],
        ['id' => 3, 'name' => 'Office Desk', 'price' => 349.5],
    ]);

    $component = Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(options: ['dataSrc' => $rows, 'selectable' => true])])
        ->set('search', 'o')
        ->set('selectedIds', ['3', '1']);

    expect(exportCsvRows($component))->toBe([['ID', 'Name', 'Price'], ['1', 'Laptop Pro', '1299.99'], ['3', 'Office Desk', '349.5']]);
});

// --- Columns and values ---

it('leaves out the row actions and the columns that are not exportable', function () {
    $dataTable = exportDataTable(columns: [
        ['title' => 'ID', 'data-field' => 'id', 'exportable' => 'false'],
        ['title' => 'Name', 'data-field' => 'name'],
    ]);

    $dataTable->initRowActions(new ComponentAttributeBag());
    $dataTable->addRowAction(new ComponentAttributeBag(['name' => 'notify', 'event' => 'product-notify']));
    $dataTable->addDataColumn(new ComponentAttributeBag(['title' => 'Category', 'data-field' => 'category']));

    $component = Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->set('search', 'Laptop');

    expect(exportCsvRows($component))->toBe([['Name', 'Category'], ['Laptop Pro', 'electronics']]);
});

it('exports what custom columns stand for', function () {
    $dataTable = exportDataTable(columns: [
        // The data field, not what the column shows
        [['title' => 'Price', 'data-field' => 'price'], '<strong>$ {{ number_format($__row->price, 2) }}</strong>'],
        // What the column shows, as plain text
        [['title' => 'Label'], <<<'BLADE'
            <td {{ $attributes }}>
                <a href="/products/{{ $__row->id }}">{{ $__row->name }}</a> &amp; <em>{{ $__row->category }}</em> #{{ $loop->iteration }}
            </td>
            BLADE],
        // The export field, in place of anything else
        [['title' => 'Created', 'data-field' => 'created_at', 'export-field' => 'category'], '{{ $__row->created_at->diffForHumans() }}'],
        ['title' => 'Name', 'data-field' => 'name', 'export-field' => 'id', 'sortable' => true],
    ]);

    $component = Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->call('setSortBy', 'name');

    expect(exportCsvRows($component))->toBe([
        ['Price', 'Label', 'Created', 'Name'],
        ['129.5', 'Bookshelf & furniture #1', 'furniture', '5'],
        ['599.5', 'Ergonomic Chair & furniture #2', 'furniture', '4'],
        ['1299.99', 'Laptop Pro & electronics #3', 'electronics', '1'],
        ['349.5', 'Office Desk & furniture #4', 'furniture', '3'],
        ['29.99', 'Wireless Mouse & electronics #5', 'electronics', '2'],
    ]);
});

it('exports dates as the table shows them', function () {
    $dataTable = exportDataTable(columns: [['title' => 'Name', 'data-field' => 'name'], ['title' => 'Created', 'data-field' => 'created_at']]);

    $component = Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->set('search', 'Laptop');

    expect(exportCsvRows($component))->toBe([['Name', 'Created'], ['Laptop Pro', '2026-10-16 14:30:05']]);
});

it('escapes formulas in CSV files', function (string $writer, bool $escape, string $expected) {
    config()->set('erickcomp-livewire-data-table.export.writer', $writer);
    config()->set('erickcomp-livewire-data-table.export.escape-formulas', $escape);
    TestProduct::query()->whereKey(1)->update(['name' => '=HYPERLINK("http://example.com","Laptop")']);
    TestProduct::query()->whereKey(2)->update(['name' => '-1']);

    $component = Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(columns: [['title' => 'Name', 'data-field' => 'name']])])
        ->set('search', 'e');

    expect(exportCsvRows($component))->toBe([['Name'], [$expected], ['Office Desk'], ['Ergonomic Chair'], ['Bookshelf']]);
})->with(['openspout', 'phpspreadsheet'])->with([
    'by default' => [true, '\'=HYPERLINK("http://example.com","Laptop")'],
    'unless disabled' => [false, '=HYPERLINK("http://example.com","Laptop")'],
]);

it('leaves alone text holding just a number', function () {
    TestProduct::query()->whereKey(2)->update(['name' => '-1']);

    $component = Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(columns: [['title' => 'Name', 'data-field' => 'name']])])
        ->set('search', '1');

    expect(exportCsvRows($component))->toBe([['Name'], ['-1']]);
});

it('downloads XLSX and ODS files, with text kept as text', function (string $writer, string $format, string $contentType) {
    config()->set('erickcomp-livewire-data-table.export.writer', $writer);
    TestProduct::query()->whereKey(1)->update(['name' => '=1+1']);

    $component = Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable()])
        ->call('export', $format)
        ->assertFileDownloaded("products-2026-10-16.$format", contentType: $contentType);

    $path = tempnam(sys_get_temp_dir(), 'lw-dt-export-test-');
    file_put_contents($path, exportedFileContent($component));
    $spreadsheet = IOFactory::load($path);
    unlink($path);

    $sheet = $spreadsheet->getActiveSheet();

    expect($sheet->getHighestRow())->toBe(6)
        ->and((string) $sheet->getCell('B1')->getValue())->toBe('Name')
        ->and((string) $sheet->getCell('B2')->getValue())->toBe('=1+1')
        ->and($sheet->getCell('B2')->isFormula())->toBeFalse()
        ->and($sheet->getCell('C3')->getValue())->toEqual(29.99);
})->with(['openspout', 'phpspreadsheet'])->with([
    'xlsx' => ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    'ods' => ['ods', 'application/vnd.oasis.opendocument.spreadsheet'],
]);

it('fills the date and time in the file name', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(['filename' => 'products {datetime}'])])
        ->call('export', 'csv')
        ->assertFileDownloaded('products 2026-10-16_14-30-05.csv');
});

it('names the file "export" with the date by default', function () {
    $dataTable = exportDataTable();
    $dataTable->setExport(new ComponentAttributeBag());

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->call('export', 'xlsx')
        ->assertFileDownloaded('export-2026-10-16.xlsx');
});

it('deletes the temporary file once it is sent', function () {
    $before = glob(sys_get_temp_dir() . '/lw-dt-export-*');

    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable()])
        ->call('export', 'xlsx')
        ->assertFileDownloaded('products-2026-10-16.xlsx');

    expect(glob(sys_get_temp_dir() . '/lw-dt-export-*'))->toBe($before);
});

// --- Checks ---

it('refuses a format the table does not offer', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(['formats' => 'csv'])])
        ->call('export', 'xlsx')
        ->assertNoFileDownloaded()
        ->assertSet('flash.0.type', 'error')
        ->assertSee('This action is not available.');
});

it('refuses to export a table without an export', function () {
    $dataTable = exportDataTable();
    $dataTable->export = null;

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->call('export', 'csv')
        ->assertNoFileDownloaded()
        ->assertSee('This action is not available.');
});

it('checks the gate', function () {
    Gate::define('export-products', fn(?object $user = null) => false);

    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(['can' => 'export-products'])])
        ->assertDontSee('Export')
        ->call('export', 'csv')
        ->assertForbidden();
});

it('exports when the gate allows it', function () {
    Gate::define('export-products', fn(?object $user = null) => true);

    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(['can' => 'export-products'])])
        ->assertSee('Export')
        ->call('export', 'csv')
        ->assertFileDownloaded('products-2026-10-16.csv');
});

it('refuses more rows than the configured limit', function () {
    config()->set('erickcomp-livewire-data-table.export.max-rows', 4);

    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable()])
        ->call('export', 'csv')
        ->assertNoFileDownloaded()
        ->assertSet('flash.0.type', 'error')
        ->assertSee('There are 5 records to export, more than the limit of 4. Narrow them down with the search or the filters.')
        ->call('applyFilters', ['category' => ['category' => 'furniture']])
        ->call('export', 'csv')
        ->assertFileDownloaded('products-2026-10-16.csv');
});

// --- Rendering ---

it('shows a button for a single format', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(['formats' => 'xlsx', 'class' => 'my-export', 'label' => 'Download'])])
        ->assertSeeHtml('<button type="button" class="my-export"')
        ->assertSeeHtml('x-on:click="$wire.export(\'xlsx\')"')
        ->assertSeeHtmlInOrder(['<span >Download</span>', '(XLSX)'])
        ->assertDontSeeHtml('aria-haspopup="menu"')
        ->assertDontSee('Export selected');
});

it('shows a menu with an entry per format', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(['formats' => 'ods,csv'])])
        ->assertSeeHtml('aria-haspopup="menu"')
        ->assertSeeHtmlInOrder([
            'x-on:click="exportMenuOpen = false; $wire.export(\'ods\')"',
            'OpenDocument (.ods)',
            'x-on:click="exportMenuOpen = false; $wire.export(\'csv\')"',
            'CSV',
        ])
        ->assertDontSee('Excel');
});

it('says so on the button when only the selected rows are exported', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(['selected-label' => 'Export the picked ones'], options: ['selectable' => true])])
        ->assertSeeHtml('<span  x-show="selectedCount === 0" >Export</span>')
        ->assertSeeHtml('<span x-show="selectedCount > 0" x-cloak>Export the picked ones</span>');
});

it('translates the export', function () {
    app()->setLocale('pt_BR');

    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(options: ['selectable' => true])])
        ->assertSee('Exportar')
        ->assertSee('Exportar selecionados');
});

it('styles the export with the preset', function (string $preset, string $buttonClass) {
    Livewire::test(LwDataTable::class, ['dataTable' => exportDataTable(options: ['preset' => $preset])])
        ->assertSeeHtml($buttonClass);
})->with([
    'vanilla' => ['vanilla', 'class="lw-dt-export-button"'],
    'bootstrap4v1' => ['bootstrap4v1', 'class="dropdown-toggle btn btn-outline-secondary mr-3 text-nowrap"'],
    'tailwind3v1' => ['tailwind3v1', 'class="inline-flex items-center gap-1 px-3 py-2'],
]);

