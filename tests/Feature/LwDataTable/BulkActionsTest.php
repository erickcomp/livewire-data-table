<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\BulkAction;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use ErickComp\LivewireDataTable\Selection\Selection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets;
use Livewire\Livewire;
use Tests\Fixtures\TestBulkActionHandlers;
use Tests\Fixtures\TestProduct;

uses(RefreshDatabase::class);

beforeEach(function () {
    TestProduct::insert([
        ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'electronics', 'price' => 1299.99, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Wireless Mouse', 'category' => 'electronics', 'price' => 29.99, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'name' => 'Office Desk', 'category' => 'furniture', 'price' => 349.00, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 4, 'name' => 'Ergonomic Chair', 'category' => 'furniture', 'price' => 599.00, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 5, 'name' => 'Bookshelf', 'category' => 'furniture', 'price' => 129.00, 'created_at' => now(), 'updated_at' => now()],
    ]);

    TestBulkActionHandlers::$calls = [];
});

/**
 * @param array<int, array<string, mixed>> $actions The attributes of each <x-data-table.bulk-action>
 */
function bulkActionsDataTable(array $actions, array $bulkActionsAttributes = [], string $preset = 'empty'): DataTable
{
    $dataTable = new DataTable(dataSrc: TestProduct::class, preset: $preset, perPage: '10');

    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => 'Name',
        'data-field' => 'name',
    ])));

    $dataTable->addFilter(new ComponentAttributeBag(['data-field' => 'category', 'label' => 'Category', 'mode' => 'exact']));

    $dataTable->initBulkActions(new ComponentAttributeBag($bulkActionsAttributes));

    foreach ($actions as $action) {
        $dataTable->addBulkAction(new ComponentAttributeBag($action));
    }

    return $dataTable;
}

function bulkHandler(string $method): string
{
    return TestBulkActionHandlers::class . '@' . $method;
}

function renderBulkActionsBlade(string $preset): string
{
    // Livewire renders each @assets block once per page: start a new page
    Livewire::flushState();

    return Blade::render(
        <<<'BLADE'
        <x-data-table :data-src="Tests\Fixtures\TestProduct::class" :preset="$preset">
            <x-data-table.bulk-actions>
                <x-data-table.bulk-action name="delete" handler="Tests\Fixtures\TestBulkActionHandlers@delete" confirm />
            </x-data-table.bulk-actions>

            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE,
        ['preset' => $preset],
        deleteCachedView: true,
    );
}

/**
 * What the table pushed with @assets: Livewire puts it in the page's <head>, not in the component's HTML
 */
function renderedAssets(): string
{
    return \implode(PHP_EOL, \Illuminate\Support\Arr::flatten(SupportScriptsAndAssets::getAssets()));
}

function remainingProductsIds(): array
{
    return TestProduct::orderBy('id')->pluck('id')->all();
}

// --- Definition ---

it('defines a bulk action from the component attributes', function () {
    $action = BulkAction::fromComponentAttributeBag(new ComponentAttributeBag([
        'name' => 'move',
        'label' => 'Move to',
        'handler' => bulkHandler('changeCategory'),
        'options' => ['books' => 'Books', 1 => 'One'],
        'confirm' => 'Move :count product(s)?',
        'confirm-title' => 'Moving',
        'can' => 'move-products',
        'keep-selection' => true,
        'class' => 'move-option',
    ]));

    expect($action->name)->toBe('move')
        ->and($action->label)->toBe('Move to')
        ->and((string) $action->handler)->toBe(TestBulkActionHandlers::class . '::changeCategory')
        ->and($action->event)->toBeNull()
        ->and($action->options)->toBe(['books' => 'Books', '1' => 'One'])
        ->and($action->requiresConfirmation)->toBeTrue()
        ->and($action->confirmMessage())->toBe('Move :count product(s)?')
        ->and($action->confirmTitle())->toBe('Moving')
        ->and($action->can)->toBe('move-products')
        ->and($action->keepSelection)->toBeTrue()
        ->and($action->attributes->getAttributes())->toBe(['class' => 'move-option']);
});

it('uses defaults for the optional attributes', function () {
    $action = new BulkAction(name: 'mark_as_sold', event: 'products-sold');

    expect($action->label)->toBe('Mark As Sold')
        ->and($action->handler)->toBeNull()
        ->and($action->event)->toBe('products-sold')
        ->and($action->options)->toBe([])
        ->and($action->requiresConfirmation)->toBeFalse()
        ->and($action->can)->toBeNull()
        ->and($action->keepSelection)->toBeFalse();
});

it('parses the confirm attribute', function (mixed $confirm, bool $requiresConfirmation, ?string $message) {
    $action = new BulkAction(name: 'delete', event: 'delete', confirm: $confirm);

    expect($action->requiresConfirmation)->toBe($requiresConfirmation)
        ->and($requiresConfirmation ? $action->confirmMessage() : null)->toBe($message);
})->with([
    'absent' => [null, false, null],
    'bare attribute' => [true, true, 'Are you sure you want to apply this action to :count record(s)?'],
    '"true"' => ['true', true, 'Are you sure you want to apply this action to :count record(s)?'],
    '"false"' => ['false', false, null],
    'false' => [false, false, null],
    'empty' => ['', false, null],
    'a question' => ['Delete :count product(s)?', true, 'Delete :count product(s)?'],
]);

it('parses the keep-selection attribute', function (bool|string $keepSelection, bool $expected) {
    expect((new BulkAction(name: 'notify', event: 'notify', keepSelection: $keepSelection))->keepSelection)->toBe($expected);
})->with([
    'bare attribute' => [true, true],
    '"true"' => ['true', true],
    '"false"' => ['false', false],
    'false' => [false, false],
]);

it('rejects invalid bulk action definitions', function (array $attributes, string $message) {
    expect(fn() => BulkAction::fromComponentAttributeBag(new ComponentAttributeBag($attributes)))
        ->toThrow(\InvalidArgumentException::class, $message);
})->with([
    'no name' => [['handler' => bulkHandler('delete')], 'The [name] attribute of an [x-data-table.bulk-action] is required'],
    'handler and event' => [['name' => 'delete', 'handler' => bulkHandler('delete'), 'event' => 'delete'], 'must have either a [handler] or an [event] attribute'],
    'neither handler nor event' => [['name' => 'delete'], 'must have either a [handler] or an [event] attribute'],
    'handler that does not exist' => [['name' => 'delete', 'handler' => 'App\\Nope@delete'], 'Invalid handler for the bulk action [delete]'],
    'closure handler' => [['name' => 'delete', 'handler' => fn() => null], 'A closure cannot be used here'],
    'options as text' => [['name' => 'move', 'event' => 'move', 'options' => 'a,b'], 'must be an array of values and labels'],
]);

it('rejects two bulk actions with the same name', function () {
    bulkActionsDataTable([
        ['name' => 'delete', 'handler' => bulkHandler('delete')],
        ['name' => 'delete', 'event' => 'delete'],
    ]);
})->throws(\InvalidArgumentException::class, 'There is more than one bulk action named [delete]');

it('makes the rows selectable and shows the records count', function () {
    $dataTable = bulkActionsDataTable([['name' => 'delete', 'handler' => bulkHandler('delete')]]);

    expect($dataTable->selectable)->toBeTrue()
        ->and($dataTable->showRecordsCount)->toBeTrue()
        ->and($dataTable->hasBulkActions())->toBeTrue();
});

it('keeps the records count off when asked to', function () {
    $dataTable = new DataTable(dataSrc: TestProduct::class, recordsCount: false);
    $dataTable->addBulkAction(new ComponentAttributeBag(['name' => 'delete', 'handler' => bulkHandler('delete')]));

    expect($dataTable->selectable)->toBeTrue()
        ->and($dataTable->showRecordsCount)->toBeFalse()
        ->and($dataTable->hasTableActions())->toBeTrue();
});

// --- Rendering ---

it('renders the actions dropdown, with an option group for the actions with options', function () {
    $dataTable = bulkActionsDataTable(
        [
            ['name' => 'delete', 'label' => 'Delete', 'handler' => bulkHandler('delete'), 'confirm' => 'Delete :count product(s)?', 'class' => 'danger'],
            ['name' => 'move', 'label' => 'Move to', 'handler' => bulkHandler('changeCategory'), 'options' => ['books' => 'Books', 'toys' => 'Toys']],
            ['name' => 'notify', 'event' => 'products-notify'],
        ],
        ['placeholder' => 'Pick an action', 'class' => 'my-select'],
    );

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('class="my-select"')
        ->assertSeeHtml('x-on:change="runBulkAction($event.target)"')
        ->assertSeeHtml('<option value="" selected>Pick an action</option>')
        ->assertSeeHtml('<option value="delete" data-lw-dt-action="delete" data-lw-dt-confirm="Delete :count product(s)?" data-lw-dt-confirm-title="Confirm" class="danger">Delete</option>')
        ->assertSeeHtml('<optgroup label="Move to">')
        ->assertSeeHtml('<option value="move:books" data-lw-dt-action="move" data-lw-dt-option="books">Books</option>')
        ->assertSeeHtml('<option value="move:toys" data-lw-dt-action="move" data-lw-dt-option="toys">Toys</option>')
        ->assertSeeHtml('<option value="notify" data-lw-dt-action="notify">Notify</option>')
        ->assertSeeHtml('async runBulkAction(select)')
        ->assertSeeHtml('type="checkbox"');
});

it('escapes the labels, options and questions', function () {
    $dataTable = bulkActionsDataTable([
        ['name' => 'move', 'label' => 'Move <b>to</b>', 'handler' => bulkHandler('changeCategory'), 'options' => ['"><script>x</script>' => 'Tom & Jerry'], 'confirm' => 'Move "them"?'],
        // A bound attribute, which Blade escapes
        ['name' => 'tag', 'label' => e('Tag & save'), 'event' => 'tag', 'confirm' => 'Tag <i>them</i>?'],
    ]);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('<optgroup label="Move &lt;b&gt;to&lt;/b&gt;">')
        ->assertSeeHtml('<option value="move:&quot;&gt;&lt;script&gt;x&lt;/script&gt;" data-lw-dt-action="move" data-lw-dt-option="&quot;&gt;&lt;script&gt;x&lt;/script&gt;" data-lw-dt-confirm="Move &quot;them&quot;?" data-lw-dt-confirm-title="Confirm">Tom &amp; Jerry</option>')
        ->assertSeeHtml('<option value="tag" data-lw-dt-action="tag" data-lw-dt-confirm="Tag &lt;i&gt;them&lt;/i&gt;?" data-lw-dt-confirm-title="Confirm">Tag &amp; save</option>')
        ->assertDontSeeHtml('<script>x</script>');
});

it('uses the translated placeholder by default', function () {
    app()->setLocale('pt_BR');

    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'delete', 'handler' => bulkHandler('delete')]])])
        ->assertSeeHtml('<option value="" selected>Ações</option>');
});

it('leaves out the actions the user cannot run', function () {
    Gate::define('delete-products', fn($user = null) => false);
    Gate::define('move-products', fn($user = null) => true);

    $dataTable = bulkActionsDataTable([
        ['name' => 'delete', 'handler' => bulkHandler('delete'), 'can' => 'delete-products'],
        ['name' => 'move', 'handler' => bulkHandler('changeCategory'), 'options' => ['books' => 'Books'], 'can' => 'move-products'],
    ]);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertDontSeeHtml('data-lw-dt-action="delete"')
        ->assertSeeHtml('data-lw-dt-action="move"');
});

it('renders no dropdown when the user cannot run any action', function () {
    Gate::define('delete-products', fn($user = null) => false);

    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'delete', 'handler' => bulkHandler('delete'), 'can' => 'delete-products']])])
        ->assertDontSeeHtml('runBulkAction($event.target)');
});

it('renders no bulk actions markup on tables without them', function () {
    $dataTable = new DataTable(dataSrc: TestProduct::class, selectable: true);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertDontSeeHtml('runBulkAction')
        ->assertDontSeeHtml('lwDataTableConfirm');
});

it('defines the confirm function even on tables without bulk actions, for the tables below them', function () {
    // Livewire outputs the assets of the first table on the page only
    Livewire::flushState();

    Blade::render(
        <<<'BLADE'
        <x-data-table :data-src="Tests\Fixtures\TestProduct::class">
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE,
        deleteCachedView: true,
    );

    expect(renderedAssets())->toContain('async function lwDataTableConfirm(message, title)');
});

it('uses the confirm dialog of the preset', function () {
    config()->set('erickcomp-livewire-data-table.presets.confirm-dialog-test', [
        'extends' => 'empty',
        'confirm-dialog' => [
            'function-name' => 'myConfirm',
            'function-code' => 'async function myConfirm(message, title) { return true; }',
        ],
    ]);

    $html = renderBulkActionsBlade('confirm-dialog-test');

    expect($html)->toContain('await myConfirm(message, picked.dataset.lwDtConfirmTitle)')
        ->not->toContain('lwDataTableConfirm')
        ->and(renderedAssets())->toContain('async function myConfirm(message, title) { return true; }')
        ->not->toContain('lwDataTableConfirm');
});

it('confirms with window.confirm() by default', function () {
    expect(renderBulkActionsBlade('empty'))->toContain('await lwDataTableConfirm(message, picked.dataset.lwDtConfirmTitle)')
        ->and(renderedAssets())->toContain('async function lwDataTableConfirm(message, title)')
        ->toContain('return window.confirm(message);');
});

// --- Running the actions ---

it('runs the handler on the selected rows, shows its message and clears the selection', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'delete', 'handler' => bulkHandler('delete')]])])
        ->set('selectedIds', ['1', '3'])
        ->call('executeBulkAction', 'delete')
        ->assertSet('selectedIds', [])
        ->assertSet('flash.0.type', 'success')
        ->assertSet('flash.0.message', '2 product(s) deleted')
        ->assertSee('2 product(s) deleted')
        ->assertSeeHtml('role="status"')
        ->assertDontSee('Laptop Pro');

    expect(remainingProductsIds())->toBe([2, 4, 5]);
});

it('hands the option over to the handler and replaces :count in its message', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'move', 'handler' => bulkHandler('changeCategory'), 'options' => ['books' => 'Books']]])])
        ->set('selectedIds', ['1', '2', '3'])
        ->call('executeBulkAction', 'move', 'books')
        ->assertSet('flash.0.type', 'info')
        ->assertSet('flash.0.message', '3 product(s) moved to books');

    expect(TestProduct::where('category', 'books')->orderBy('id')->pluck('id')->all())->toBe([1, 2, 3]);
});

it('hands the selection, the option and the action over to the handler', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'record', 'handler' => bulkHandler('record'), 'options' => ['a' => 'A']]])])
        ->set('selectedIds', ['4', '2'])
        ->call('executeBulkAction', 'record', 'a')
        ->assertSet('flash.0.message', 'The action was applied to 2 record(s).');

    expect(TestBulkActionHandlers::$calls)->toBe([['ids' => ['2', '4'], 'option' => 'a', 'action' => 'record']]);
});

it('runs the action on all the rows matching the filters, but the ones left out', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'delete', 'handler' => bulkHandler('delete')]])])
        ->call('applyFilters', ['category' => ['category' => 'furniture']])
        ->call('selectAllMatching')
        ->set('selectedIds', ['4'])
        ->call('executeBulkAction', 'delete')
        ->assertSet('flash.0.message', '2 product(s) deleted')
        ->assertSet('selectionMode', Selection::MODE_IDS)
        ->assertSet('selectedIds', []);

    expect(remainingProductsIds())->toBe([1, 2, 4]);
});

it('keeps the selection when asked to', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'record', 'handler' => bulkHandler('record'), 'keep-selection' => true]])])
        ->set('selectedIds', ['1', '2'])
        ->call('executeBulkAction', 'record')
        ->assertSet('selectedIds', ['1', '2']);
});

it('dispatches the event of an event action', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'notify', 'event' => 'products-notify', 'options' => ['email' => 'E-mail']]])])
        ->set('selectedIds', ['5', '3'])
        ->call('executeBulkAction', 'notify', 'email')
        ->assertDispatched('products-notify', action: 'notify', option: 'email', ids: ['3', '5'], count: 2)
        ->assertSet('selectedIds', [])
        ->assertSet('flash', []);
});

it('refuses an action that does not exist, or an option it does not have', function (string $name, ?string $option) {
    $dataTable = bulkActionsDataTable([
        ['name' => 'delete', 'handler' => bulkHandler('delete')],
        ['name' => 'move', 'handler' => bulkHandler('changeCategory'), 'options' => ['books' => 'Books']],
    ]);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->set('selectedIds', ['1'])
        ->call('executeBulkAction', $name, $option)
        ->assertSet('flash.0.type', 'error')
        ->assertSet('flash.0.message', 'This action is not available.')
        ->assertSeeHtml('role="alert"')
        ->assertSet('selectedIds', ['1']);

    expect(remainingProductsIds())->toBe([1, 2, 3, 4, 5])
        ->and(TestProduct::where('category', 'books')->count())->toBe(0);
})->with([
    'unknown action' => ['archive', null],
    'unknown option' => ['move', 'toys'],
    'missing option' => ['move', null],
    'option of an action without options' => ['delete', 'books'],
]);

it('refuses to run without selected rows', function (array $selectedIds) {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'delete', 'handler' => bulkHandler('delete')]])])
        ->set('selectedIds', $selectedIds)
        ->call('executeBulkAction', 'delete')
        ->assertSet('flash.0.type', 'error')
        ->assertSet('flash.0.message', 'Select at least one record to run this action.');

    expect(remainingProductsIds())->toBe([1, 2, 3, 4, 5]);
})->with([
    'nothing selected' => [[]],
    'rows that are not in the table' => [['99']],
]);

it('checks the gate ability of the action', function () {
    Gate::define('delete-products', fn($user = null) => false);

    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'delete', 'handler' => bulkHandler('delete'), 'can' => 'delete-products']])])
        ->set('selectedIds', ['1'])
        ->call('executeBulkAction', 'delete')
        ->assertForbidden();

    expect(remainingProductsIds())->toBe([1, 2, 3, 4, 5]);
});

it('runs the action when the gate allows it', function () {
    Gate::define('delete-products', fn($user = null) => true);

    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'delete', 'handler' => bulkHandler('delete'), 'can' => 'delete-products']])])
        ->set('selectedIds', ['1'])
        ->call('executeBulkAction', 'delete');

    expect(remainingProductsIds())->toBe([2, 3, 4, 5]);
});

it('shows errors as such and keeps the selection', function (string $method, string $message) {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'change', 'handler' => bulkHandler($method)]])])
        ->set('selectedIds', ['1', '2'])
        ->call('executeBulkAction', 'change')
        ->assertSet('flash.0.type', 'error')
        ->assertSet('flash.0.message', $message)
        ->assertSee($message)
        ->assertSet('selectedIds', ['1', '2']);
})->with([
    'ErrorMessageForUserException' => ['failWithException', 'The products are locked'],
    'ActionResult::error()' => ['failWithResult', 'Some products could not be changed'],
]);

it('redirects when the handler returns a redirect', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'go', 'handler' => bulkHandler('redirect')]])])
        ->set('selectedIds', ['1'])
        ->call('executeBulkAction', 'go')
        ->assertRedirect('/products/done')
        ->assertSet('flash', []);
});

it('downloads the file the handler returns', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'export', 'handler' => bulkHandler('download')]])])
        ->set('selectedIds', ['3', '1'])
        ->call('executeBulkAction', 'export')
        ->assertFileDownloaded('products.csv', 'Laptop Pro,Office Desk')
        ->assertSet('selectedIds', []);
});

it('drops the messages on the next request', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'delete', 'handler' => bulkHandler('delete')]])])
        ->set('selectedIds', ['1'])
        ->call('executeBulkAction', 'delete')
        ->assertSee('1 product(s) deleted')
        ->call('setSortBy', 'name')
        ->assertSet('flash', [])
        ->assertDontSee('1 product(s) deleted');
});

it('does not let the browser write the messages', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => bulkActionsDataTable([['name' => 'delete', 'handler' => bulkHandler('delete')]])])
        ->set('flash', [['type' => 'success', 'message' => 'Fake', 'key' => 'x']]);
})->throws(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
