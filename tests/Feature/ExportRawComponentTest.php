<?php

use ErickComp\LivewireDataTable\DataTable\Export;
use ErickComp\LivewireDataTable\Export\OpenSpoutWriter;
use ErickComp\LivewireDataTable\Export\PhpSpreadsheetWriter;
use Illuminate\Support\Facades\Blade;

/**
 * Renders the Blade and returns the root cause of the error it throws (see BulkActionsRawComponentTest.php)
 */
function exportBladeError(string $blade): ?\Throwable
{
    try {
        Blade::render($blade, deleteCachedView: true);
    } catch (\Throwable $e) {
        while ($e->getPrevious() !== null && !$e instanceof \LogicException && !$e instanceof \ValueError) {
            $e = $e->getPrevious();
        }

        return $e;
    }

    return null;
}

it('builds the export from the component, with the other attributes on its button', function () {
    $html = Blade::render(
        <<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product', 'secret' => 's3cr3t']])" preset="empty">
            <x-data-table.column title="Name" data-field="name" export-field="id" />
            <x-data-table.column title="Secret" data-field="secret" exportable="false" td-class="secret-td" />
            <x-data-table.export formats="xlsx" filename="products-{date}" label="Download" max-execution-time="300" class="export-button" data-test="export" />
        </x-data-table>
        BLADE,
        deleteCachedView: true,
    );

    expect($html)->toContain('<button type="button" class="export-button" data-test="export"')
        ->toContain('x-on:click="$wire.export(\'xlsx\')"')
        ->toContain('<span >Download</span>')
        ->toContain('<td class="secret-td">')
        ->not->toContain('exportable')
        ->not->toContain('export-field')
        ->not->toContain('filename')
        ->not->toContain('execution');
});

it('reads the time limit of the export', function (mixed $maxExecutionTime, ?int $expected) {
    expect((new Export(maxExecutionTime: $maxExecutionTime))->maxExecutionTime)->toBe($expected);
})->with([
    'not given' => [null, null],
    'blank' => [' ', null],
    'seconds' => ['300', 300],
    'seconds with spaces' => [' 300 ', 300],
    'bound seconds' => [600, 600],
    'no limit' => ['0', 0],
]);

it('only accepts the export as a direct child of the table', function (string $blade) {
    $error = exportBladeError($blade);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('You can only use the [x-data-table.export] as a direct child of the [x-data-table] component');
})->with([
    'outside of a table' => ['<x-data-table.export />'],
    'inside a column' => [<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.column title="Name" data-field="name">
                <x-data-table.export />
            </x-data-table.column>
        </x-data-table>
        BLADE],
]);

it('only accepts one export per table', function () {
    $error = exportBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.export formats="csv" />
            <x-data-table.export formats="xlsx" />
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('You can only have one [x-data-table.export] component per [x-data-table] component');
});

it('requires the export component to be self-closing', function () {
    $error = exportBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.export>Export</x-data-table.export>
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('The [x-data-table.export] component must be self-closing (ending with "/>") and have no content');
});

it('reports an invalid export definition', function (string $attributes, string $exception, string $message) {
    $error = exportBladeError(<<<BLADE
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.column title="Name" data-field="name" />
            <x-data-table.export $attributes />
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf($exception)
        ->and($error->getMessage())->toBe($message);
})->with([
    'unknown format' => ['formats="csv,pdf"', \InvalidArgumentException::class, 'Invalid export format: [pdf]. The ones the export writer [' . OpenSpoutWriter::class . '] supports are: csv, xlsx and ods'],
    'filename with a slash' => ['filename="../products"', \InvalidArgumentException::class, 'Invalid export filename: [../products]. It cannot have slashes or control characters'],
    'time limit that is not a number' => ['max-execution-time="5min"', \InvalidArgumentException::class, 'Invalid value for the attribute [max-execution-time] of the export: [5min]. It must be a whole number of seconds (0 for no limit)'],
    'negative time limit' => ['max-execution-time="-1"', \InvalidArgumentException::class, 'Invalid value for the attribute [max-execution-time] of the export: [-1]. It must be a whole number of seconds (0 for no limit)'],
    'fractional time limit' => ['max-execution-time="1.5"', \InvalidArgumentException::class, 'Invalid value for the attribute [max-execution-time] of the export: [1.5]. It must be a whole number of seconds (0 for no limit)'],
]);

it('reports an invalid exportable attribute', function () {
    $error = exportBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.column title="Name" data-field="name" exportable="maybe" />
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\ValueError::class)
        ->and($error->getMessage())->toBe('Invalid value for the attribute [exportable] of the column [Name]: "maybe". It must be a boolean (true, false, 1, 0, yes, no, on or off)');
});

it('asks to install a spreadsheet library when none is', function () {
    foreach ([OpenSpoutWriter::class, PhpSpreadsheetWriter::class] as $writer) {
        app()->bind($writer, fn() => new class extends PhpSpreadsheetWriter {
            public function isAvailable(): bool
            {
                return false;
            }
        });
    }

    $error = exportBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.export />
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toStartWith('To export a data table, please install one of the packages: openspout/openspout');
});

it('leaves the markup alone when the table has no export', function () {
    $html = Blade::render(
        <<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE,
        deleteCachedView: true,
    );

    expect($html)->not->toContain('export');
});
