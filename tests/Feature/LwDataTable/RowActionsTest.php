<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\RowAction;
use ErickComp\LivewireDataTable\DataTable\RowActionsColumn;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;
use Tests\Fixtures\TestProduct;
use Tests\Fixtures\TestRowActionHandlers;

uses(RefreshDatabase::class);

beforeEach(function () {
    TestProduct::insert([
        ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'electronics', 'price' => 1299.99, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Wireless Mouse', 'category' => 'electronics', 'price' => 29.99, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'name' => 'Office Desk', 'category' => 'furniture', 'price' => 349.00, 'created_at' => now(), 'updated_at' => now()],
    ]);

    Route::get('/products/{product}', fn() => 'product')->name('products.show');
    Route::delete('/products/{product}', fn() => 'deleted')->name('products.destroy');
    Route::getRoutes()->refreshNameLookups();

    TestRowActionHandlers::$calls = [];
});

/**
 * @param array<int, array<string, mixed>> $actions The attributes of each <x-data-table.row-action>
 */
function rowActionsDataTable(array $actions, array $rowActionsAttributes = [], array $tableOptions = []): DataTable
{
    $dataTable = new DataTable(
        dataSrc: $tableOptions['dataSrc'] ?? TestProduct::class,
        preset: $tableOptions['preset'] ?? 'empty',
        perPage: '10',
        rowClick: $tableOptions['rowClick'] ?? null,
        rowClickUrl: $tableOptions['rowClickUrl'] ?? null,
    );

    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag(['title' => 'Name', 'data-field' => 'name'])));
    $dataTable->addFilter(new ComponentAttributeBag(['data-field' => 'category', 'label' => 'Category', 'mode' => 'exact']));

    $dataTable->initRowActions(new ComponentAttributeBag($rowActionsAttributes));

    foreach ($actions as $action) {
        $dataTable->addRowAction(new ComponentAttributeBag($action));
    }

    return $dataTable;
}

function rowHandler(string $method): string
{
    return TestRowActionHandlers::class . '@' . $method;
}

function remainingRowActionProductsIds(): array
{
    return TestProduct::orderBy('id')->pluck('id')->all();
}

// --- Definition ---

it('defines a row action from the component attributes', function () {
    $action = RowAction::fromComponentAttributeBag(new ComponentAttributeBag([
        'name' => 'archive',
        'label' => 'Archive',
        'url' => '/products/{id}',
        'method' => 'DELETE',
        'target' => '_blank',
        'confirm' => 'Archive {name}?',
        'confirm-title' => 'Archiving',
        'can' => 'archive',
        'class' => 'danger',
    ]));

    expect($action->name)->toBe('archive')
        ->and($action->label)->toBe('Archive')
        ->and($action->url)->toBe('/products/{id}')
        ->and($action->method)->toBe('delete')
        ->and($action->target)->toBe('_blank')
        ->and($action->isLink())->toBeTrue()
        ->and($action->submitsForm())->toBeTrue()
        ->and($action->requiresConfirmation)->toBeTrue()
        ->and($action->confirmMessage(['name' => 'Desk']))->toBe('Archive Desk?')
        ->and($action->confirmTitle())->toBe('Archiving')
        ->and($action->can)->toBe('archive')
        ->and($action->attributes->getAttributes())->toBe(['class' => 'danger']);
});

it('uses defaults for the optional attributes', function () {
    $action = new RowAction(name: 'send_invoice', event: 'invoice-send', confirm: true);

    expect($action->label)->toBe('Send Invoice')
        ->and($action->method)->toBe('get')
        ->and($action->isLink())->toBeFalse()
        ->and($action->confirmMessage())->toBe('Are you sure?')
        ->and($action->confirmTitle())->toBe('Confirm');
});

it('fills the url placeholders with the row values, URL-encoded', function () {
    $action = new RowAction(name: 'edit', url: '/products/{id}/edit?name={name}&owner={owner.name}&missing={nope}');

    expect($action->href(['id' => 7, 'name' => 'A&B / C', 'owner' => ['name' => 'Zoë']]))
        ->toBe('/products/7/edit?name=A%26B%20%2F%20C&owner=Zo%C3%AB&missing=');
});

it('builds the route of the row', function () {
    $action = new RowAction(name: 'show', route: 'products.show', routeParams: ['product' => 'id']);

    expect($action->href(TestProduct::find(3)))->toBe(route('products.show', ['product' => 3]));
});

it('rejects invalid row action definitions', function (array $attributes, string $message) {
    expect(fn() => RowAction::fromComponentAttributeBag(new ComponentAttributeBag($attributes)))
        ->toThrow(\InvalidArgumentException::class, $message);
})->with([
    'no name' => [['url' => '/x'], 'The [name] attribute of an [x-data-table.row-action] is required'],
    'no destination' => [['name' => 'edit'], 'must have one (and only one) of the [url], [route], [handler] and [event] attributes'],
    'two destinations' => [['name' => 'edit', 'url' => '/x', 'event' => 'edit'], 'must have one (and only one) of the [url], [route], [handler] and [event] attributes'],
    'javascript url' => [['name' => 'edit', 'url' => 'javascript:alert(1)'], 'Only http(s) and relative URLs are allowed'],
    'javascript url with a tab' => [['name' => 'edit', 'url' => "java\tscript:alert(1)"], 'Only http(s) and relative URLs are allowed'],
    'data url' => [['name' => 'edit', 'url' => ' DATA:text/html,x'], 'Only http(s) and relative URLs are allowed'],
    'unknown route' => [['name' => 'show', 'route' => 'nope.show'], 'The route [nope.show] of the row action [show] does not exist'],
    'route params as text' => [['name' => 'show', 'route' => 'products.show', 'route-params' => 'id'], 'must be an array of parameters and row fields'],
    'route params without a route' => [['name' => 'show', 'url' => '/x', 'route-params' => ['product' => 'id']], 'The [route-params] attribute of the row action [show] needs a [route]'],
    'invalid method' => [['name' => 'edit', 'url' => '/x', 'method' => 'head'], 'Invalid method for the row action [edit]: [head]'],
    'method of a handler' => [['name' => 'delete', 'handler' => rowHandler('delete'), 'method' => 'delete'], 'The [method] and [target] attributes of the row action [delete] need a [url] or a [route]'],
    'target of an event' => [['name' => 'notify', 'event' => 'notify', 'target' => '_blank'], 'need a [url] or a [route]'],
    'closure handler' => [['name' => 'delete', 'handler' => fn() => null], 'A closure cannot be used here'],
]);

it('accepts http(s) and relative urls', function (string $url) {
    expect((new RowAction(name: 'go', url: $url))->url)->toBe($url);
})->with(['/products/{id}', 'products/{id}', '?id={id}', '#row-{id}', 'https://example.com/{id}', 'HTTP://example.com', '//example.com/{id}']);

it('does not let empty values turn a url into another kind of url', function () {
    (new RowAction(name: 'go', url: 'java{nothing}script:alert(1)'))->href(['nothing' => '']);
})->throws(\InvalidArgumentException::class, 'Only http(s) and relative URLs are allowed');

it('rejects two row actions with the same name', function () {
    rowActionsDataTable([
        ['name' => 'edit', 'url' => '/x'],
        ['name' => 'edit', 'event' => 'edit'],
    ]);
})->throws(\InvalidArgumentException::class, 'There is more than one row action named [edit]');

it('rejects an invalid display', function () {
    new RowActionsColumn(display: 'menu');
})->throws(\InvalidArgumentException::class, 'Invalid display for the row actions: [menu]');

it('adds the column where it is declared, neither sortable nor searchable', function () {
    $dataTable = new DataTable(dataSrc: TestProduct::class);
    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag(['title' => 'Name', 'data-field' => 'name'])));
    $dataTable->initRowActions(new ComponentAttributeBag(['title' => 'Do', 'th-class' => 'actions-th', 'td-class' => 'actions-td']));
    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag(['title' => 'Price', 'data-field' => 'price'])));
    $dataTable->addRowAction(new ComponentAttributeBag(['name' => 'edit', 'url' => '/products/{id}']));

    expect($dataTable->columns->map(fn($column) => $column->title)->all())->toBe(['Name', 'Do', 'Price'])
        ->and($dataTable->rowActions->isSortable())->toBeFalse()
        ->and($dataTable->rowActions->isSearchable())->toBeFalse();

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtmlInOrder(['Name', '<th class="actions-th">', 'Do', 'Price'])
        ->assertSeeHtml('<td class="actions-td">');
});

it('validates the row click attributes', function (array $options, string $exception, string $message) {
    try {
        Livewire::test(LwDataTable::class, ['dataTable' => rowActionsDataTable([['name' => 'edit', 'url' => '/x']], tableOptions: $options)]);
    } catch (\Throwable $error) {
        // Livewire may mount the component while rendering a view, which wraps the error
        while ($error->getPrevious() !== null && !$error instanceof $exception) {
            $error = $error->getPrevious();
        }
    }

    expect($error ?? null)->toBeInstanceOf($exception)
        ->and($error->getMessage())->toContain($message);
})->with([
    'both' => [['rowClick' => 'edit', 'rowClickUrl' => '/x'], \InvalidArgumentException::class, 'either a [row-click] or a [row-click-url] attribute'],
    'javascript url' => [['rowClickUrl' => 'javascript:alert(1)'], \InvalidArgumentException::class, 'Invalid URL for the [row-click-url] of the table'],
    'unknown action' => [['rowClick' => 'show'], \LogicException::class, 'names the row action [show], but there is no row action with that name'],
]);

// --- Rendering ---

it('renders the actions side by side', function () {
    $dataTable = rowActionsDataTable(
        [
            ['name' => 'show', 'label' => 'Show', 'route' => 'products.show', 'route-params' => ['product' => 'id']],
            ['name' => 'edit', 'label' => 'Edit', 'url' => '/products/{id}/edit?name={name}', 'target' => '_blank'],
        ],
        ['display' => 'links'],
    );

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('<a href="' . route('products.show', ['product' => 1]) . '" class="" data-lw-dt-row-action="show"')
        ->assertSeeHtml('<a href="/products/2/edit?name=Wireless%20Mouse" target="_blank" rel="noopener" class="" data-lw-dt-row-action="edit"')
        ->assertDontSeeHtml('rowActionsOpen');
});

it('renders a dropdown when there is more than one action', function () {
    $dataTable = rowActionsDataTable([
        ['name' => 'show', 'label' => 'Show', 'route' => 'products.show', 'route-params' => ['product' => 'id']],
        ['name' => 'edit', 'label' => 'Edit', 'url' => '/products/{id}/edit'],
    ]);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('x-on:click="toggleRowActions()"')
        ->assertSeeHtml('<ul role="menu" x-show="rowActionsOpen"')
        ->assertSeeHtml('<a href="/products/3/edit" class="" role="menuitem" data-lw-dt-row-action="edit"');
});

it('renders a single action as a link, as in Magento', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => rowActionsDataTable([['name' => 'edit', 'label' => 'Edit', 'url' => '/products/{id}/edit']])])
        ->assertSeeHtml('<a href="/products/1/edit" class="" data-lw-dt-row-action="edit"')
        ->assertSeeHtml('>Edit</a>')
        ->assertDontSeeHtml('rowActionsOpen');
});

it('renders a form with the CSRF token for any other method than GET', function () {
    $dataTable = rowActionsDataTable(
        [
            ['name' => 'archive', 'label' => 'Archive', 'route' => 'products.destroy', 'route-params' => ['product' => 'id'], 'method' => 'delete', 'confirm' => 'Archive {name}?'],
            ['name' => 'touch', 'label' => 'Touch', 'url' => '/products/{id}/touch', 'method' => 'post'],
        ],
        ['display' => 'links'],
    );

    $html = Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])->html();

    expect($html)->toContain('action="' . route('products.destroy', ['product' => 1]) . '" data-lw-dt-confirm="Archive Laptop Pro?" data-lw-dt-confirm-title="Confirm"')
        ->toContain('x-on:submit.prevent="submitRowActionForm($el)"')
        ->toContain('<input type="hidden" name="_token"')
        ->toContain('<input type="hidden" name="_method" value="DELETE">')
        ->toContain('<button type="submit" class="" data-lw-dt-row-action="archive">Archive</button>')
        ->toContain('action="/products/2/touch"')
        ->and(\substr_count($html, 'name="_method"'))->toBe(3);
});

it('renders a button for the actions run on the server, with the row id and the question', function () {
    $dataTable = rowActionsDataTable([['name' => 'delete', 'label' => 'Delete', 'handler' => rowHandler('delete'), 'confirm' => 'Delete {name}?', 'class' => 'danger']]);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('data-lw-dt-row-id="3" data-lw-dt-confirm="Delete Office Desk?" data-lw-dt-confirm-title="Confirm" class="danger" data-lw-dt-row-action="delete"')
        ->assertSeeHtml('x-on:click="runRowAction($el)">Delete</button>')
        ->assertSeeHtml('async runRowAction(button)');
});

it('escapes the row values in the questions', function () {
    TestProduct::whereKey(1)->update(['name' => '"><script>x</script>']);

    Livewire::test(LwDataTable::class, ['dataTable' => rowActionsDataTable([['name' => 'delete', 'handler' => rowHandler('delete'), 'confirm' => 'Delete {name}?']])])
        ->assertSeeHtml('data-lw-dt-confirm="Delete &quot;&gt;&lt;script&gt;x&lt;/script&gt;?"')
        ->assertDontSeeHtml('<script>x</script>');
});

it('shows each row only the actions the user may run on it', function () {
    Gate::define('delete-product', fn($user, TestProduct $product) => $product->category === 'furniture');

    $dataTable = rowActionsDataTable([
        ['name' => 'show', 'label' => 'Show', 'route' => 'products.show', 'route-params' => ['product' => 'id']],
        ['name' => 'delete', 'label' => 'Delete', 'handler' => rowHandler('delete'), 'can' => 'delete-product'],
    ]);

    $this->actingAs(new \Illuminate\Foundation\Auth\User());

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('data-lw-dt-row-id="3"')
        ->assertDontSeeHtml('data-lw-dt-row-id="1"')
        ->assertDontSeeHtml('data-lw-dt-row-id="2"')
        // One action left on rows 1 and 2: a link; two on row 3: a dropdown
        ->assertSeeHtml('<a href="' . route('products.show', ['product' => 1]) . '" class="" data-lw-dt-row-action="show"')
        ->assertSeeHtml('<a href="' . route('products.show', ['product' => 3]) . '" class="" role="menuitem" data-lw-dt-row-action="show"');
});

it('hands array rows over to the gate as they are', function () {
    Gate::define('touch-row', fn($user, array $row) => $row['id'] === 2);

    $dataTable = rowActionsDataTable(
        [['name' => 'touch', 'event' => 'row-touch', 'can' => 'touch-row']],
        tableOptions: ['dataSrc' => collect([['id' => 1, 'name' => 'One', 'category' => 'a'], ['id' => 2, 'name' => 'Two', 'category' => 'b']])],
    );

    $this->actingAs(new \Illuminate\Foundation\Auth\User());

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('data-lw-dt-row-id="2"')
        ->assertDontSeeHtml('data-lw-dt-row-id="1"');
});

it('names the column with the translated title by default', function () {
    app()->setLocale('pt_BR');

    Livewire::test(LwDataTable::class, ['dataTable' => rowActionsDataTable([['name' => 'edit', 'url' => '/x']])])
        ->assertSee('Ações');
});

it('renders no row actions markup on tables without them', function () {
    $dataTable = new DataTable(dataSrc: TestProduct::class);
    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag(['title' => 'Name', 'data-field' => 'name'])));

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertDontSeeHtml('data-lw-dt-row')
        ->assertDontSeeHtml('onRowClick')
        ->assertDontSeeHtml('runRowAction');
});

// --- Row click ---

it('makes the rows clickable with row-click', function () {
    $dataTable = rowActionsDataTable(
        [['name' => 'show', 'route' => 'products.show', 'route-params' => ['product' => 'id']]],
        tableOptions: ['rowClick' => 'show', 'preset' => 'vanilla'],
    );

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('<tr data-lw-dt-row-id="1" data-lw-dt-row-click-action="show" x-on:click="onRowClick($event)" class="lw-dt-clickable-row"')
        ->assertSeeHtml('onRowClick(event)');
});

it('makes the rows lead to row-click-url', function () {
    $dataTable = rowActionsDataTable([], tableOptions: ['rowClickUrl' => '/products/{id}?from={category}']);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('<tr data-lw-dt-row-id="3" data-lw-dt-row-click-url="/products/3?from=furniture" x-on:click="onRowClick($event)"')
        ->assertSeeHtml('onRowClick(event)');
});

it('leaves out of the row click the rows whose action the user cannot run', function () {
    Gate::define('show-product', fn($user, TestProduct $product) => $product->id !== 2);

    $dataTable = rowActionsDataTable(
        [['name' => 'show', 'route' => 'products.show', 'route-params' => ['product' => 'id'], 'can' => 'show-product']],
        tableOptions: ['rowClick' => 'show'],
    );

    $this->actingAs(new \Illuminate\Foundation\Auth\User());

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSeeHtml('<tr data-lw-dt-row-id="1" data-lw-dt-row-click-action="show"')
        ->assertDontSeeHtml('<tr data-lw-dt-row-id="2"')
        ->assertSeeHtml('<tr data-lw-dt-row-id="3" data-lw-dt-row-click-action="show"');
});

// --- Running the actions ---

it('runs the handler on the row and shows its message', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => rowActionsDataTable([['name' => 'delete', 'handler' => rowHandler('delete')]])])
        ->call('executeRowAction', 'delete', '2')
        ->assertSet('flash.0.type', 'success')
        ->assertSet('flash.0.message', 'Wireless Mouse deleted')
        ->assertSee('Wireless Mouse deleted')
        ->assertDontSeeHtml('data-lw-dt-row-id="2"');

    expect(remainingRowActionProductsIds())->toBe([1, 3]);
});

it('hands the row, its id, a selection of it and the action over to the handler', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => rowActionsDataTable([['name' => 'record', 'handler' => rowHandler('record')]])])
        ->call('executeRowAction', 'record', '3')
        ->assertSet('flash.0.message', 'Done.');

    expect(TestRowActionHandlers::$calls)->toBe([['row' => 'Office Desk', 'id' => '3', 'selectionIds' => ['3'], 'action' => 'record']]);
});

it('dispatches the event of an event action', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => rowActionsDataTable([['name' => 'notify', 'event' => 'product-notify']])])
        ->call('executeRowAction', 'notify', '1')
        ->assertDispatched('product-notify', action: 'notify', id: '1')
        ->assertSet('flash', []);
});

it('refuses a row that is not among the table rows', function (array $filters, string $rowId) {
    Livewire::test(LwDataTable::class, ['dataTable' => rowActionsDataTable([['name' => 'delete', 'handler' => rowHandler('delete')]])])
        ->call('applyFilters', $filters)
        ->call('executeRowAction', 'delete', $rowId)
        ->assertSet('flash.0.type', 'error')
        ->assertSet('flash.0.message', 'This record is no longer available.');

    expect(remainingRowActionProductsIds())->toBe([1, 2, 3]);
})->with([
    'a row that does not exist' => [[], '99'],
    'a row left out by the filters' => [['category' => ['category' => 'furniture']], '1'],
    'an id of another kind' => [[], 'abc'],
]);

it('refuses an action that does not exist or does not run on the server', function (string $name) {
    $dataTable = rowActionsDataTable([
        ['name' => 'edit', 'url' => '/products/{id}/edit'],
        ['name' => 'delete', 'handler' => rowHandler('delete')],
    ]);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->call('executeRowAction', $name, '1')
        ->assertSet('flash.0.type', 'error')
        ->assertSet('flash.0.message', 'This action is not available.');

    expect(remainingRowActionProductsIds())->toBe([1, 2, 3]);
})->with(['unknown action' => ['archive'], 'link action' => ['edit']]);

it('checks the gate ability of the action with the row', function () {
    Gate::define('delete-product', fn($user, TestProduct $product) => $product->id === 3);

    $this->actingAs(new \Illuminate\Foundation\Auth\User());

    $dataTable = rowActionsDataTable([['name' => 'delete', 'handler' => rowHandler('delete'), 'can' => 'delete-product']]);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->call('executeRowAction', 'delete', '1')
        ->assertForbidden();

    expect(remainingRowActionProductsIds())->toBe([1, 2, 3]);

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->call('executeRowAction', 'delete', '3')
        ->assertSet('flash.0.message', 'Office Desk deleted');

    expect(remainingRowActionProductsIds())->toBe([1, 2]);
});

it('shows the error of a failing handler', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => rowActionsDataTable([['name' => 'lock', 'handler' => rowHandler('fail')]])])
        ->call('executeRowAction', 'lock', '1')
        ->assertSet('flash.0.type', 'error')
        ->assertSet('flash.0.message', 'The product is locked');
});

it('redirects when the handler returns a redirect', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => rowActionsDataTable([['name' => 'go', 'handler' => rowHandler('redirect')]])])
        ->call('executeRowAction', 'go', '2')
        ->assertRedirect('/products/2/done');
});
