<?php

use Illuminate\Support\Facades\Blade;

/**
 * Renders the Blade and returns the root cause of the error it throws: the guards throw a LogicException, which the
 * view wraps (more than once when it comes from a nested Blade pass, like a column's custom renderer)
 */
function bulkActionsBladeError(string $blade): ?\Throwable
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

it('builds the bulk actions from the components', function () {
    $html = Blade::render(
        <<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.bulk-actions placeholder="Pick one" class="actions-select">
                <x-data-table.bulk-action name="delete" label="Delete" handler="Tests\Fixtures\TestBulkActionHandlers@delete" confirm="Delete :count row(s)?" confirm-title="Deleting" />
                <x-data-table.bulk-action name="move" label="Move to" handler="Tests\Fixtures\TestBulkActionHandlers@changeCategory" :options="['books' => 'Books']" />
                <x-data-table.bulk-action name="notify" event="rows-notify" keep-selection />
            </x-data-table.bulk-actions>

            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE,
        deleteCachedView: true,
    );

    expect($html)->toContain('class="actions-select"')
        ->toContain('<option value="" selected>Pick one</option>')
        ->toContain('<option value="delete" data-lw-dt-action="delete" data-lw-dt-confirm="Delete :count row(s)?" data-lw-dt-confirm-title="Deleting">Delete</option>')
        ->toContain('<optgroup label="Move to">')
        ->toContain('<option value="move:books" data-lw-dt-action="move" data-lw-dt-option="books">Books</option>')
        ->toContain('<option value="notify" data-lw-dt-action="notify">Notify</option>')
        // The rows became selectable
        ->toContain('x-on:change="toggleRow(\'1\')"');
});

it('only accepts the bulk actions as a direct child of the table', function (string $blade) {
    $error = bulkActionsBladeError($blade);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('You can only use the [x-data-table.bulk-actions] as a direct child of the [x-data-table] component');
})->with([
    'outside of a table' => [<<<'BLADE'
        <x-data-table.bulk-actions>
            <x-data-table.bulk-action name="notify" event="rows-notify" />
        </x-data-table.bulk-actions>
        BLADE],
    'inside a column' => [<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.column title="Name" data-field="name">
                <x-data-table.bulk-actions>
                    <x-data-table.bulk-action name="notify" event="rows-notify" />
                </x-data-table.bulk-actions>
            </x-data-table.column>
        </x-data-table>
        BLADE],
]);

it('only accepts one set of bulk actions per table', function () {
    $error = bulkActionsBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.bulk-actions>
                <x-data-table.bulk-action name="notify" event="rows-notify" />
            </x-data-table.bulk-actions>
            <x-data-table.bulk-actions>
                <x-data-table.bulk-action name="archive" event="rows-archive" />
            </x-data-table.bulk-actions>
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('You can only have one [x-data-table.bulk-actions] component per [x-data-table] component');
});

it('only accepts a bulk action as a direct child of the bulk actions', function (string $blade) {
    $error = bulkActionsBladeError($blade);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('You can only use the [x-data-table.bulk-action] as a direct child of the [x-data-table.bulk-actions] component');
})->with([
    'directly in the table' => [<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.bulk-action name="notify" event="rows-notify" />
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE],
    'inside the filters' => [<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.filters>
                <x-data-table.bulk-action name="notify" event="rows-notify" />
            </x-data-table.filters>
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE],
]);

it('requires the bulk action component to be self-closing', function () {
    $error = bulkActionsBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.bulk-actions>
                <x-data-table.bulk-action name="notify" event="rows-notify">Notify</x-data-table.bulk-action>
            </x-data-table.bulk-actions>
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('The [x-data-table.bulk-action] component must be self-closing (ending with "/>") and have no content');
});

it('reports an invalid bulk action definition', function () {
    $error = bulkActionsBladeError(<<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.bulk-actions>
                <x-data-table.bulk-action name="notify" />
            </x-data-table.bulk-actions>
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE);

    expect($error)->toBeInstanceOf(\InvalidArgumentException::class)
        ->and($error->getMessage())->toBe('The bulk action [notify] must have either a [handler] or an [event] attribute (and only one of them)');
});
