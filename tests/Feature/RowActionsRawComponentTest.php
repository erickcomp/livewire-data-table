<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/products/{product}', fn() => 'product')->name('products.show');
    Route::getRoutes()->refreshNameLookups();
});

/**
 * Renders the Blade and returns the root cause of the error it throws (see BulkActionsRawComponentTest.php)
 */
function rowActionsBladeError(string $blade): ?\Throwable
{
    try {
        Blade::render($blade, deleteCachedView: true);
    } catch (\Throwable $e) {
        while ($e->getPrevious() !== null && !$e instanceof \LogicException && !$e instanceof \InvalidArgumentException) {
            $e = $e->getPrevious();
        }

        return $e;
    }

    return null;
}

it('builds the row actions column from the components, where it is declared', function () {
    $html = Blade::render(
        <<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty" row-click="show">
            <x-data-table.column title="Name" data-field="name" />

            <x-data-table.row-actions title="Do" display="links" td-class="actions-td">
                <x-data-table.row-action name="show" label="Show" route="products.show" :route-params="['product' => 'id']" />
                <x-data-table.row-action name="edit" label="Edit" url="/products/{id}/edit" target="_blank" />
                <x-data-table.row-action name="delete" label="Delete" handler="Tests\Fixtures\TestRowActionHandlers@delete" confirm="Delete {name}?" />
            </x-data-table.row-actions>

            <x-data-table.column title="ID" data-field="id" />
        </x-data-table>
        BLADE,
        deleteCachedView: true,
    );

    expect($html)->toContain('<tr data-lw-dt-row-id="1" data-lw-dt-row-click-action="show" x-on:click="onRowClick($event)"')
        ->toContain('<td class="actions-td">')
        ->toContain('<a href="' . route('products.show', ['product' => 1]) . '" class="" data-lw-dt-row-action="show"')
        ->toContain('<a href="/products/1/edit" target="_blank" rel="noopener" class="" data-lw-dt-row-action="edit"')
        ->toContain('data-lw-dt-row-id="1" data-lw-dt-confirm="Delete Product?" data-lw-dt-confirm-title="Confirm" class="" data-lw-dt-row-action="delete"')
        ->toMatch('/<th[^>]*>\s*Name\b.*?<th[^>]*>\s*Do\b.*?<th[^>]*>\s*ID\b/s');
});

it('only accepts the row actions as a direct child of the table', function (string $blade) {
    $error = rowActionsBladeError($blade);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('You can only use the [x-data-table.row-actions] as a direct child of the [x-data-table] component');
})->with([
    'outside of a table' => [<<<'BLADE'
        <x-data-table.row-actions>
            <x-data-table.row-action name="notify" event="row-notify" />
        </x-data-table.row-actions>
        BLADE],
    'inside a column' => [<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.column title="Name" data-field="name">
                <x-data-table.row-actions>
                    <x-data-table.row-action name="notify" event="row-notify" />
                </x-data-table.row-actions>
            </x-data-table.column>
        </x-data-table>
        BLADE],
]);

it('only accepts one row actions column per table', function () {
    $error = rowActionsBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.row-actions>
                <x-data-table.row-action name="notify" event="row-notify" />
            </x-data-table.row-actions>
            <x-data-table.row-actions>
                <x-data-table.row-action name="archive" event="row-archive" />
            </x-data-table.row-actions>
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('You can only have one [x-data-table.row-actions] component per [x-data-table] component');
});

it('only accepts a row action as a direct child of the row actions', function (string $blade) {
    $error = rowActionsBladeError($blade);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('You can only use the [x-data-table.row-action] as a direct child of the [x-data-table.row-actions] component');
})->with([
    'directly in the table' => [<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.row-action name="notify" event="row-notify" />
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE],
    'inside the bulk actions' => [<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.bulk-actions>
                <x-data-table.row-action name="notify" event="row-notify" />
            </x-data-table.bulk-actions>
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE],
]);

it('requires the row action component to be self-closing', function () {
    $error = rowActionsBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.row-actions>
                <x-data-table.row-action name="notify" event="row-notify">Notify</x-data-table.row-action>
            </x-data-table.row-actions>
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('The [x-data-table.row-action] component must be self-closing (ending with "/>") and have no content');
});

it('reports an invalid row action definition', function () {
    $error = rowActionsBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.row-actions>
                <x-data-table.row-action name="edit" url="javascript:alert(1)" />
            </x-data-table.row-actions>
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\InvalidArgumentException::class)
        ->and($error->getMessage())->toBe('Invalid URL for the row action [edit]: [javascript:alert(1)]. Only http(s) and relative URLs are allowed');
});

it('reports a row click naming an action that does not exist', function () {
    $error = rowActionsBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty" row-click="show">
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('The [row-click] attribute of the table names the row action [show], but there is no row action with that name');
});
