<?php

use ErickComp\LivewireDataTable\Export\ExportWriter;
use ErickComp\LivewireDataTable\Export\ExportWriterResolver;
use ErickComp\LivewireDataTable\Export\OpenSpoutWriter;
use ErickComp\LivewireDataTable\Export\PhpSpreadsheetWriter;
use ErickComp\LivewireDataTable\Export\TableExporter;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Tests\Fixtures\TestExportWriter;

enum ExportTestStatus: string
{
    case Active = 'active';
}

enum ExportTestLevel: int
{
    case High = 3;
}

enum ExportTestSize
{
    case Large;
}

function exportWriterPath(): string
{
    return test()->exportPath = tempnam(sys_get_temp_dir(), 'lw-dt-export-test-');
}

afterEach(function () {
    if (isset($this->exportPath) && is_file($this->exportPath)) {
        unlink($this->exportPath);
    }
});

/**
 * @return Generator<string, array{ExportWriter}>
 */
function exportWriters(): Generator
{
    yield 'OpenSpout' => [new OpenSpoutWriter()];
    yield 'PhpSpreadsheet' => [new PhpSpreadsheetWriter()];
}

/**
 * Reads a spreadsheet back, each cell as [value, is text]. PhpSpreadsheet reads the files of both writers: OpenSpout's
 * reader takes any text starting with "=" for a formula, whatever the file says.
 *
 * @return array<int, array<int, array{mixed, bool}>>
 */
function readExportedSpreadsheet(string $path): array
{
    $rows = [];
    $spreadsheet = IOFactory::load($path);

    foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row) {
        $cells = [];

        foreach ($row->getCellIterator() as $cell) {
            // OpenSpout writes XLSX text inline, which PhpSpreadsheet reads as rich text
            $value = $cell->getValue();

            $cells[] = [
                $value instanceof RichText ? $value->getPlainText() : $value,
                \in_array($cell->getDataType(), [DataType::TYPE_STRING, DataType::TYPE_INLINE], true),
            ];
        }

        $rows[] = $cells;
    }

    $spreadsheet->disconnectWorksheets();

    return $rows;
}

// --- Writers ---

it('writes CSV files alike, with a BOM and quotes only where needed', function (ExportWriter $writer) {
    $path = exportWriterPath();

    $writer->write('csv', ['ID', 'Name', 'Price'], [
        [1, 'Laptop, "Pro"', 1299.99],
        [2, 'Ação', null],
        [3, "Two\nlines", -5],
    ], $path);

    expect(file_get_contents($path))->toBe("\u{FEFF}ID,Name,Price\n1,\"Laptop, \"\"Pro\"\"\",1299.99\n2,Ação,\n3,\"Two\nlines\",-5\n");
})->with(exportWriters());

it('writes the CSV delimiter of the config', function (ExportWriter $writer) {
    config()->set('erickcomp-livewire-data-table.export.csv-delimiter', ';');
    $path = exportWriterPath();

    $writer->write('csv', ['ID', 'Name'], [[1, 'Laptop; Pro']], $path);

    expect(file_get_contents($path))->toBe("\u{FEFF}ID;Name\n1;\"Laptop; Pro\"\n");
})->with(exportWriters());

it('writes XLSX and ODS files, with text kept as text', function (ExportWriter $writer, string $format) {
    $path = exportWriterPath();

    $writer->write($format, ['ID', 'Name', 'Price'], (function () {
        // The rows may come from a generator
        yield [1, 'Laptop Pro', 1299.99];
        yield [2, '=1+1', null];
        yield [3, '0042', 0];
    })(), $path);

    $rows = readExportedSpreadsheet($path);

    expect($rows[0])->toBe([['ID', true], ['Name', true], ['Price', true]])
        ->and($rows[1])->toEqual([[1, false], ['Laptop Pro', true], [1299.99, false]])
        ->and($rows[2][1])->toBe(['=1+1', true])
        ->and($rows[3][1])->toBe(['0042', true])
        ->and($rows[3][2][0])->toEqual(0);
})->with(exportWriters())->with(['xlsx', 'ods']);

it('supports CSV, XLSX and ODS', function (ExportWriter $writer) {
    expect($writer->isAvailable())->toBeTrue()
        ->and($writer->supportedFormats())->toBe(['csv', 'xlsx', 'ods']);
})->with(exportWriters());

it('refuses a format it does not write', function (ExportWriter $writer) {
    $writer->write('pdf', ['ID'], [], exportWriterPath());
})->with(exportWriters())->throws(InvalidArgumentException::class, 'cannot write the format [pdf]');

// --- Resolver ---

function unavailableExportWriter(string $class): void
{
    app()->bind($class, fn() => new class extends OpenSpoutWriter {
        public function isAvailable(): bool
        {
            return false;
        }
    });
}

it('prefers OpenSpout under "auto"', function (?string $config) {
    config()->set('erickcomp-livewire-data-table.export.writer', $config);

    expect(app(ExportWriterResolver::class)->resolve())->toBeInstanceOf(OpenSpoutWriter::class);
})->with(['auto' => ['auto'], 'not set' => [null], 'upper case' => ['AUTO']]);

it('falls back to PhpSpreadsheet under "auto" when OpenSpout is not installed', function () {
    unavailableExportWriter(OpenSpoutWriter::class);

    expect(app(ExportWriterResolver::class)->resolve())->toBeInstanceOf(PhpSpreadsheetWriter::class);
});

it('asks to install a library when none is', function () {
    unavailableExportWriter(OpenSpoutWriter::class);
    unavailableExportWriter(PhpSpreadsheetWriter::class);

    app(ExportWriterResolver::class)->resolve();
})->throws(LogicException::class, 'To export a data table, please install one of the packages: openspout/openspout (recommended: it writes the rows as they come, with constant memory) or phpoffice/phpspreadsheet');

it('uses the writer named in the config', function (string $config, string $class) {
    config()->set('erickcomp-livewire-data-table.export.writer', $config);

    expect(app(ExportWriterResolver::class)->resolve())->toBeInstanceOf($class);
})->with([
    'openspout' => ['openspout', OpenSpoutWriter::class],
    'phpspreadsheet' => ['PhpSpreadsheet', PhpSpreadsheetWriter::class],
    'a class' => [TestExportWriter::class, TestExportWriter::class],
]);

it('asks to install the library of the writer named in the config', function () {
    config()->set('erickcomp-livewire-data-table.export.writer', 'phpspreadsheet');
    unavailableExportWriter(PhpSpreadsheetWriter::class);

    app(ExportWriterResolver::class)->resolve();
})->throws(LogicException::class, 'The data table exports are set to use the writer [phpspreadsheet] (config "erickcomp-livewire-data-table.export.writer"): please install the package phpoffice/phpspreadsheet');

it('refuses a writer that is not one', function (string $config) {
    config()->set('erickcomp-livewire-data-table.export.writer', $config);

    app(ExportWriterResolver::class)->resolve();
})->with([
    'unknown name' => ['excel'],
    'class that is not a writer' => [stdClass::class],
])->throws(InvalidArgumentException::class, 'Invalid export writer');

// --- Values ---

it('turns values into text or numbers, as the table shows them', function (mixed $value, mixed $expected) {
    expect(TableExporter::toCellValue($value))->toBe($expected);
})->with([
    'null' => [null, null],
    'text' => ['Laptop', 'Laptop'],
    'integer' => [42, 42],
    'float' => [12.5, 12.5],
    'infinite float' => [INF, 'INF'],
    'true' => [true, 1],
    'false' => [false, 0],
    'backed enum' => [ExportTestStatus::Active, 'active'],
    'int backed enum' => [ExportTestLevel::High, 3],
    'pure enum' => [ExportTestSize::Large, 'Large'],
    'Carbon date' => [Carbon::parse('2026-10-16 14:30:05'), '2026-10-16 14:30:05'],
    'other date' => [new DateTimeImmutable('2026-10-16 14:30:05'), '2026-10-16 14:30:05'],
    'Htmlable' => [new HtmlString('<b>Bold</b> &amp;   <i>more</i>'), 'Bold & more'],
    'Stringable' => [str('Laptop'), 'Laptop'],
    'array' => [['a' => 1, 'b' => 'ç'], '{"a":1,"b":"ç"}'],
    'collection' => [collect([1, 2]), '[1,2]'],
]);

it('escapes text that a spreadsheet would run as a formula', function (mixed $value, mixed $expected) {
    expect(TableExporter::escapeFormula($value))->toBe($expected);
})->with([
    'equals' => ['=HYPERLINK("http://example.com")', '\'=HYPERLINK("http://example.com")'],
    'plus' => ['+1+1', "'+1+1"],
    'minus' => ['-1+1', "'-1+1"],
    'at' => ['@SUM(A1)', "'@SUM(A1)"],
    'tab' => ["\t=1", "'\t=1"],
    'carriage return' => ["\r=1", "'\r=1"],
    'negative number as text' => ['-12.5', '-12.5'],
    'positive number as text' => ['+12', '+12'],
    'text' => ['Laptop = 1', 'Laptop = 1'],
    'empty text' => ['', ''],
    'number' => [-5, -5],
    'null' => [null, null],
]);
