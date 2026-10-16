<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;
use Tests\Fixtures\TestInlineEditHandlers;
use Tests\Fixtures\TestProduct;

uses(RefreshDatabase::class);

beforeEach(function () {
    $created = Carbon::parse('2026-10-16 14:30:05');

    TestProduct::insert([
        ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'electronics', 'price' => 1299.99, 'created_at' => $created, 'updated_at' => $created],
        ['id' => 2, 'name' => 'Wireless Mouse', 'category' => 'electronics', 'price' => 29.99, 'created_at' => $created, 'updated_at' => $created],
        ['id' => 3, 'name' => 'Office Desk', 'category' => 'furniture', 'price' => 349.5, 'created_at' => $created, 'updated_at' => $created],
    ]);

    TestInlineEditHandlers::$calls = [];
});

function inlineEditHandler(string $method): string
{
    return TestInlineEditHandlers::class . '@' . $method;
}

/**
 * The default columns: ID (read only), Name (text, required), Category (select) and Price (number)
 *
 * @return array<int, array<string, mixed>>
 */
function inlineEditColumns(): array
{
    return [
        ['title' => 'ID', 'data-field' => 'id'],
        ['title' => 'Name', 'data-field' => 'name', 'editor' => 'text', 'editor-rules' => 'required|max:30'],
        ['title' => 'Category', 'data-field' => 'category', 'editor' => 'select', 'editor-options' => ['electronics' => 'Electronics', 'furniture' => 'Furniture']],
        ['title' => 'Price', 'data-field' => 'price', 'editor' => 'number', 'editor-rules' => ['required', 'min:0']],
    ];
}

/**
 * @param array<int, array<string, mixed>>|null $columns The attributes of each column
 * @param array<int, array<string, mixed>>|null $rowActions The attributes of each row action, when the table has them
 */
function inlineEditDataTable(array $options = [], ?array $columns = null, ?array $rowActions = null, array $rowActionsAttributes = []): DataTable
{
    $dataTable = new DataTable(
        dataSrc: $options['dataSrc'] ?? TestProduct::class,
        preset: $options['preset'] ?? 'empty',
        perPage: $options['perPage'] ?? '10',
        rowClick: $options['rowClick'] ?? null,
        inlineEdit: array_key_exists('inlineEdit', $options) ? $options['inlineEdit'] : inlineEditHandler('save'),
        inlineEditTrigger: $options['trigger'] ?? null,
        inlineEditCan: $options['can'] ?? null,
    );

    $dataTable->search = new Search(new ComponentAttributeBag(['data-fields' => 'name']));

    foreach ($columns ?? inlineEditColumns() as $column) {
        $dataTable->addDataColumn(new ComponentAttributeBag($column));
    }

    if ($rowActions !== null) {
        $dataTable->initRowActions(new ComponentAttributeBag($rowActionsAttributes));

        foreach ($rowActions as $rowAction) {
            $dataTable->addRowAction(new ComponentAttributeBag($rowAction));
        }
    }

    return $dataTable;
}

function inlineEditTable(array $options = [], ?array $columns = null, ?array $rowActions = null, array $rowActionsAttributes = []): mixed
{
    return Livewire::test(LwDataTable::class, ['dataTable' => inlineEditDataTable($options, $columns, $rowActions, $rowActionsAttributes)]);
}

// --- Editing ---

it('loads the editable fields of the row', function () {
    inlineEditTable()
        ->call('editRow', '3')
        ->assertSet('editingRowId', '3')
        ->assertSet('editingValues', ['name' => 'Office Desk', 'category' => 'furniture', 'price' => 349.5]);
});

it('loads values the way their inputs take them', function () {
    inlineEditTable(columns: [
        ['title' => 'Name', 'data-field' => 'name', 'editor' => 'textarea'],
        ['title' => 'Created', 'data-field' => 'created_at', 'editor' => 'datetime'],
        ['title' => 'Day', 'data-field' => 'updated_at', 'editor' => 'date'],
    ])
        ->call('editRow', '1')
        ->assertSet('editingValues', ['name' => 'Laptop Pro', 'created_at' => '2026-10-16T14:30', 'updated_at' => '2026-10-16']);
});

it('keeps the id of the row as the row has it', function () {
    inlineEditTable()
        ->call('editRow', '03')
        ->assertSet('editingRowId', '3')
        ->assertSeeHtml('x-on:keydown="onInlineEditKeydown($event)"');
});

it('does not let the browser pick the row being edited', function () {
    inlineEditTable()->set('editingRowId', '1');
})->throws(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

it('edits only the rows of the table', function (string $rowId) {
    inlineEditTable()
        ->set('search', 'Laptop')
        ->call('editRow', $rowId)
        ->assertSet('editingRowId', null)
        ->assertSet('editingValues', [])
        ->assertSee('This record is no longer available.');
})->with([
    'filtered out' => ['2'],
    'unknown' => ['999'],
]);

it('edits nothing on a table without inline editing', function () {
    inlineEditTable(['inlineEdit' => null], columns: [['title' => 'Name', 'data-field' => 'name']])
        ->call('editRow', '1')
        ->assertSet('editingRowId', null)
        ->assertSee('This action is not available.');
});

it('drops the edit when the row leaves the page', function () {
    inlineEditTable()
        ->call('editRow', '1')
        ->set('editingValues.name', 'Changed')
        ->set('search', 'Desk')
        ->assertSet('editingRowId', null)
        ->assertSet('editingValues', []);
});

it('cancels the edit', function () {
    inlineEditTable()
        ->call('editRow', '1')
        ->set('editingValues.name', '')
        ->call('saveInlineEdit')
        ->assertHasErrors('editingValues.name')
        ->call('cancelInlineEdit')
        ->assertSet('editingRowId', null)
        ->assertSet('editingValues', [])
        ->assertHasNoErrors();

    expect(TestInlineEditHandlers::$calls)->toBe([]);
});

it('does not take the validation rule names from the messages', function () {
    // Kept apart: assertHasErrors() tells failed rules by name only from the validator that failed them
    inlineEditTable()
        ->call('editRow', '1')
        ->set('editingValues.name', '')
        ->call('saveInlineEdit')
        ->assertHasErrors(['editingValues.name' => 'The Name field is required.']);
});

// --- Saving ---

it('hands the handler the editable fields, converted, and leaves the edit', function () {
    inlineEditTable()
        ->call('editRow', '2')
        ->set('editingValues.name', 'Silent Mouse')
        ->set('editingValues.category', 'furniture')
        ->set('editingValues.price', '19.9')
        // Not an editable field: never read
        ->set('editingValues.id', '999')
        ->call('saveInlineEdit')
        ->assertHasNoErrors()
        ->assertSet('editingRowId', null)
        ->assertSet('editingValues', [])
        ->assertSee('Saved.')
        ->assertSee('Silent Mouse');

    expect(TestInlineEditHandlers::$calls)->toBe([
        ['id' => '2', 'values' => ['name' => 'Silent Mouse', 'category' => 'furniture', 'price' => 19.9], 'row' => 'Wireless Mouse'],
    ])
        ->and(TestProduct::find(2)->only(['id', 'name', 'category']))->toBe(['id' => 2, 'name' => 'Silent Mouse', 'category' => 'furniture']);
});

it('validates the values with the rules of the columns and the types of their editors', function (string $field, mixed $value, string $message) {
    inlineEditTable()
        ->call('editRow', '1')
        ->set("editingValues.$field", $value)
        ->call('saveInlineEdit')
        ->assertHasErrors(["editingValues.$field"])
        ->assertSee($message)
        ->assertSet('editingRowId', '1');

    expect(TestInlineEditHandlers::$calls)->toBe([]);
})->with([
    'required' => ['name', '', 'The Name field is required.'],
    'max' => ['name', str_repeat('a', 31), 'The Name field must not be greater than 30 characters.'],
    'not a number' => ['price', 'cheap', 'The Price field must be a number.'],
    'min' => ['price', '-1', 'The Price field must be at least 0.'],
    'not an option' => ['category', 'toys', 'The selected Category is invalid.'],
    'not text' => ['name', ['an', 'array'], 'The Name field must be a string.'],
]);

it('puts the row id in the rules', function () {
    $columns = [['title' => 'Name', 'data-field' => 'name', 'editor' => 'text', 'editor-rules' => 'required|unique:test_products,name,{id}']];

    inlineEditTable(columns: $columns)
        ->call('editRow', '1')
        ->call('saveInlineEdit')
        ->assertHasNoErrors()
        ->call('editRow', '1')
        ->set('editingValues.name', 'Office Desk')
        ->call('saveInlineEdit')
        ->assertHasErrors(['editingValues.name' => 'The Name has already been taken.']);
});

it('lets the rules compare the fields by their own names', function () {
    $rows = collect([['id' => 1, 'name' => 'Promo', 'starts' => '2026-10-01', 'ends' => '2026-10-31']]);

    inlineEditTable(['dataSrc' => $rows, 'inlineEdit' => inlineEditHandler('record')], columns: [
        ['title' => 'Name', 'data-field' => 'name'],
        ['title' => 'Starts', 'data-field' => 'starts', 'editor' => 'date'],
        ['title' => 'Ends', 'data-field' => 'ends', 'editor' => 'date', 'editor-rules' => 'after:starts'],
    ])
        ->call('editRow', '1')
        ->set('editingValues.ends', '2026-09-30')
        ->call('saveInlineEdit')
        ->assertHasErrors(['editingValues.ends' => 'The Ends field must be a date after Starts.'])
        ->set('editingValues.ends', '2026-11-30')
        ->call('saveInlineEdit')
        ->assertHasNoErrors();

    expect(TestInlineEditHandlers::$calls[0]['values'])->toBe(['starts' => '2026-10-01', 'ends' => '2026-11-30']);
});

it('shows the validation errors of the handler next to the fields', function () {
    inlineEditTable(['inlineEdit' => inlineEditHandler('rejectName')])
        ->call('editRow', '1')
        ->call('saveInlineEdit')
        ->assertHasErrors(['editingValues.name'])
        ->assertSee('This name is taken.')
        ->assertSet('editingRowId', '1');
});

it('keeps editing when the handler fails with a message for the user', function () {
    inlineEditTable(['inlineEdit' => inlineEditHandler('fail')])
        ->call('editRow', '1')
        ->call('saveInlineEdit')
        ->assertSee('The product is locked')
        ->assertSet('flash.0.type', 'error')
        ->assertSet('editingRowId', '1');
});

it('shows the message the handler returns', function () {
    inlineEditTable(['inlineEdit' => inlineEditHandler('message')])
        ->call('editRow', '1')
        ->set('editingValues.name', 'Laptop Air')
        ->call('saveInlineEdit')
        ->assertSee('Laptop Air saved')
        ->assertSet('editingRowId', null);
});

it('drops the edit of a row that left the table meanwhile', function () {
    $component = inlineEditTable()->call('editRow', '1');

    TestProduct::find(1)->delete();

    $component->call('saveInlineEdit')
        ->assertSee('This record is no longer available.')
        ->assertSet('editingRowId', null);

    expect(TestInlineEditHandlers::$calls)->toBe([]);
});

it('saves the values of a collection, converted for each editor', function () {
    $rows = collect([
        ['id' => 1, 'name' => 'Laptop', 'active' => 1, 'stock' => 5, 'since' => '2026-01-02 10:00:00', 'notes' => null],
        ['id' => 2, 'name' => 'Mouse', 'active' => 0, 'stock' => null, 'since' => null, 'notes' => 'Wireless'],
    ]);

    inlineEditTable(['dataSrc' => $rows, 'inlineEdit' => inlineEditHandler('record')], columns: [
        ['title' => 'Name', 'data-field' => 'name'],
        ['title' => 'Active', 'data-field' => 'active', 'editor' => 'checkbox'],
        ['title' => 'Stock', 'data-field' => 'stock', 'editor' => 'number'],
        ['title' => 'Since', 'data-field' => 'since', 'editor' => 'date'],
        ['title' => 'Notes', 'data-field' => 'notes', 'editor' => 'textarea'],
    ])
        ->call('editRow', '1')
        ->assertSet('editingValues', ['active' => true, 'stock' => 5, 'since' => '2026-01-02', 'notes' => ''])
        ->set('editingValues.active', false)
        ->set('editingValues.stock', '')
        ->call('saveInlineEdit')
        ->assertHasNoErrors()
        ->call('editRow', '2')
        ->assertSet('editingValues', ['active' => false, 'stock' => '', 'since' => '', 'notes' => 'Wireless'])
        ->set('editingValues.stock', '7')
        ->call('saveInlineEdit');

    expect(TestInlineEditHandlers::$calls)->toBe([
        ['id' => '1', 'values' => ['active' => false, 'stock' => null, 'since' => '2026-01-02', 'notes' => ''], 'row' => 'Laptop'],
        ['id' => '2', 'values' => ['active' => false, 'stock' => 7, 'since' => null, 'notes' => 'Wireless'], 'row' => 'Mouse'],
    ]);
});

it('edits the field the editor names', function () {
    $rows = collect([['id' => 1, 'name' => 'Laptop', 'owner' => ['name' => 'Ana'], 'owner_label' => 'Owned by Ana']]);

    inlineEditTable(['dataSrc' => $rows, 'inlineEdit' => inlineEditHandler('record')], columns: [
        ['title' => 'Name', 'data-field' => 'name'],
        ['title' => 'Owner', 'data-field' => 'owner_label', 'editor' => 'text', 'editor-field' => 'owner.name', 'editor-rules' => 'required'],
    ])
        ->call('editRow', '1')
        ->assertSet('editingValues', ['owner' => ['name' => 'Ana']])
        ->set('editingValues.owner.name', '')
        ->call('saveInlineEdit')
        ->assertHasErrors(['editingValues.owner.name'])
        ->assertSee('The Owner field is required.')
        ->set('editingValues.owner.name', 'Bia')
        ->call('saveInlineEdit')
        ->assertHasNoErrors();

    expect(TestInlineEditHandlers::$calls[0]['values'])->toBe(['owner' => ['name' => 'Bia']]);
});

// --- Gate ---

it('checks the gate with the row', function () {
    Gate::define('edit-product', fn(?object $user, TestProduct $product) => $product->id !== 2);

    $component = inlineEditTable(['can' => 'edit-product'])
        ->call('editRow', '1')
        ->assertSet('editingRowId', '1');

    $component->call('editRow', '2')->assertForbidden();
});

it('checks the gate again when saving', function () {
    $allowed = true;
    Gate::define('edit-product', function (?object $user, TestProduct $product) use (&$allowed) {
        return $allowed;
    });

    $component = inlineEditTable(['can' => 'edit-product'])->call('editRow', '1');

    $allowed = false;

    $component->call('saveInlineEdit')->assertForbidden();

    expect(TestInlineEditHandlers::$calls)->toBe([]);
});

// --- Rendering ---

it('turns the editable cells of the row being edited into inputs', function () {
    inlineEditTable(columns: [
        ['title' => 'ID', 'data-field' => 'id'],
        ['title' => 'Name', 'data-field' => 'name', 'editor' => 'text', 'editor-placeholder' => 'Product name', 'td-class' => 'name-td'],
        ['title' => 'Category', 'data-field' => 'category', 'editor' => 'select', 'editor-options' => ['electronics' => 'Electronics', 'furniture' => 'Furniture']],
        ['title' => 'Price', 'data-field' => 'price', 'editor' => 'number'],
        ['title' => 'Notes', 'data-field' => 'name', 'editor' => 'textarea', 'editor-field' => 'notes'],
    ], options: ['dataSrc' => collect([['id' => 1, 'name' => 'Laptop', 'category' => 'electronics', 'price' => 10, 'notes' => '']])])
        ->assertDontSeeHtml('wire:model="editingValues')
        ->call('editRow', '1')
        ->assertSeeHtml('x-on:keydown="onInlineEditKeydown($event)"')
        ->assertSeeHtml('<td class="name-td">')
        // The first input takes the focus; "editor-" attributes go to the inputs
        ->assertSeeHtml('<input type="text" wire:model="editingValues.name" aria-label="Name" x-init="$nextTick(() => $el.focus())" placeholder="Product name" />')
        ->assertSeeHtmlInOrder(['<select wire:model="editingValues.category" aria-label="Category">', '<option value="electronics">Electronics</option>', '<option value="furniture">Furniture</option>'])
        ->assertSeeHtml('<input type="number" wire:model="editingValues.price" aria-label="Price" step="any" />')
        ->assertSeeHtml('<textarea wire:model="editingValues.notes" aria-label="Notes"></textarea>');
});

it('shows the validation messages under the inputs', function () {
    inlineEditTable(['preset' => 'bootstrap4v1'])
        ->call('editRow', '1')
        ->set('editingValues.name', '')
        ->call('saveInlineEdit')
        ->assertSeeHtml('class="form-control form-control-sm is-invalid" wire:model="editingValues.name" aria-label="Name" aria-invalid="true"')
        ->assertSeeHtml('<div class="invalid-feedback d-block">The Name field is required.</div>');
});

it('puts the buttons in a column of their own without row actions', function () {
    $component = inlineEditTable()
        ->assertSeeHtmlInOrder(['Price', '<th class="">Actions</th>'])
        ->assertSeeHtml('x-on:click="$wire.editRow(\'1\')">Edit</button>')
        ->assertDontSeeHtml('x-on:click="$wire.saveInlineEdit()"');

    $component->call('editRow', '1')
        ->assertSeeHtml('x-on:click="$wire.saveInlineEdit()">Save</button>')
        ->assertSeeHtml('x-on:click="$wire.cancelInlineEdit()">Cancel</button>')
        ->assertDontSeeHtml('x-on:click="$wire.editRow(\'1\')"')
        ->assertSeeHtml('x-on:click="$wire.editRow(\'2\')"');
});

it('accounts for the column of the buttons when there are no rows', function () {
    inlineEditTable()
        ->set('search', 'nothing like this')
        ->assertSeeHtml('colspan="5"');
});

it('puts the buttons among the row actions', function (string $display, string $editButton) {
    inlineEditTable(rowActions: [['name' => 'notify', 'event' => 'product-notify']], rowActionsAttributes: ['display' => $display])
        ->assertDontSeeHtml('<th class="">Actions</th>')
        ->assertSeeHtmlInOrder([$editButton, 'data-lw-dt-row-action="notify"'])
        ->tap(fn($component) => expect(substr_count($component->html(), 'data-lw-dt-row-action="notify"'))->toBe(3))
        ->call('editRow', '1')
        ->assertSeeHtml('x-on:click="$wire.saveInlineEdit()">Save</button>')
        // Save and Cancel take the place of the actions on the row being edited, and only there
        ->tap(fn($component) => expect(substr_count($component->html(), 'data-lw-dt-row-action="notify"'))->toBe(2));
})->with([
    'side by side' => ['links', '<button type="button" data-lw-dt-inline-edit class="" '],
    'in the dropdown' => ['dropdown', '<button type="button" data-lw-dt-inline-edit class="" role="menuitem"'],
]);

it('shows the Edit button only on the rows the user may edit', function () {
    Gate::define('edit-product', fn(?object $user, TestProduct $product) => $product->id !== 2);

    inlineEditTable(['can' => 'edit-product'])
        ->assertSeeHtml('$wire.editRow(\'1\')')
        ->assertDontSeeHtml('$wire.editRow(\'2\')')
        ->assertSeeHtml('$wire.editRow(\'3\')');
});

it('edits a row when it is clicked, before its row-click action', function () {
    Gate::define('edit-product', fn(?object $user, TestProduct $product) => $product->id !== 2);

    inlineEditTable(
        ['trigger' => 'row-click', 'can' => 'edit-product', 'rowClick' => 'notify'],
        rowActions: [['name' => 'notify', 'event' => 'product-notify']],
    )
        ->assertSeeHtml('<tr data-lw-dt-row-id="1" data-lw-dt-row-click-edit="" x-on:click="onRowClick($event)"')
        // The user may not edit it: the row-click action applies
        ->assertSeeHtml('<tr data-lw-dt-row-id="2" data-lw-dt-row-click-action="notify" x-on:click="onRowClick($event)"')
        ->assertDontSeeHtml('data-lw-dt-inline-edit')
        ->call('editRow', '1')
        // The row being edited takes no clicks
        ->assertDontSeeHtml('<tr data-lw-dt-row-id="1"')
        ->assertSeeHtml('<tr data-lw-dt-row-id="3" data-lw-dt-row-click-edit=""');
});

it('styles the row being edited with the preset', function () {
    inlineEditTable(['preset' => 'vanilla'])
        ->call('editRow', '1')
        ->assertSeeHtml('<tr class="lw-dt-editing-row" wire:key="1"');
});

it('translates the buttons', function () {
    app()->setLocale('pt_BR');

    inlineEditTable()
        ->assertSee('Editar')
        ->call('editRow', '1')
        ->assertSee('Salvar')
        ->assertSee('Cancelar');
});

// --- Definition ---

it('reads the editor of a column', function () {
    $column = DataTable\DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => 'Price',
        'data-field' => 'price',
        'editor' => ' NUMBER ',
        'editor-rules' => 'required|min:0',
        'editor-step' => '0.01',
    ]));

    expect($column->editor)->toEqual(new DataTable\ColumnEditor('number', 'price', [], ['required', 'min:0']))
        ->and($column->editorInputAttributes->getAttributes())->toBe(['step' => '0.01'])
        ->and($column->editor->validationRules('7'))->toBe(['nullable', 'numeric', 'required', 'min:0']);
});

it('needs a field to edit', function () {
    DataTable\CustomRenderedColumn::fromComponentAttributeBag(new ComponentAttributeBag(['title' => 'Name', 'editor' => 'text']), customRendererCode: '{{ $__row->name }}');
})->throws(InvalidArgumentException::class, 'The column [Name] has an [editor], but no field to edit: give it a [data-field] or an [editor-field]');

it('requires a handler for the editors, and editors for the handler', function (array $options, ?array $columns, string $message) {
    try {
        inlineEditTable($options, $columns);
    } catch (\Throwable $error) {
        // Thrown when the table mounts, inside the view of the test
        while (!$error instanceof LogicException && $error->getPrevious() !== null) {
            $error = $error->getPrevious();
        }
    }

    expect($error ?? null)->toBeInstanceOf(LogicException::class)
        ->and($error->getMessage())->toBe($message);
})->with([
    'editors without a handler' => [['inlineEdit' => null], null, 'The column [Name] has an [editor], but the table has no [inline-edit] handler to save the changes'],
    'a handler without editors' => [[], [['title' => 'Name', 'data-field' => 'name']], 'The table has an [inline-edit] handler, but none of its columns has an [editor]'],
    'two editors of a field' => [[], [['title' => 'Name', 'data-field' => 'name', 'editor' => 'text'], ['title' => 'Title', 'data-field' => 'name', 'editor' => 'textarea']], 'The columns [Name] and [Title] edit the same field: [name]'],
]);

it('reports an invalid table definition', function (array $options, string $message) {
    expect(fn() => inlineEditDataTable($options))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'trigger' => [['trigger' => 'double-click'], 'Invalid value for the attribute [inline-edit-trigger]: [double-click]. The valid ones are: button and row-click'],
    'handler' => [['inlineEdit' => 'NoSuchClass@save'], 'Invalid handler for the [inline-edit] of the table'],
]);

it('reports an invalid editor', function (array $column, string $message) {
    expect(fn() => inlineEditDataTable(columns: [$column]))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'type' => [['title' => 'Name', 'data-field' => 'name', 'editor' => 'color'], 'Invalid editor for the column [Name]: [color]. The valid ones are: text, number, select, date, datetime, checkbox, textarea'],
    'select without options' => [['title' => 'Category', 'data-field' => 'category', 'editor' => 'select'], 'The column [Category] has a select editor, but no [editor-options]'],
    'options as text' => [['title' => 'Category', 'data-field' => 'category', 'editor' => 'select', 'editor-options' => 'a,b'], 'The editor options of the column [Category] must be an array of values and labels'],
    'closure rule' => [['title' => 'Name', 'data-field' => 'name', 'editor' => 'text', 'editor-rules' => [fn() => true]], 'Invalid editor rule for the column [Name]'],
]);
