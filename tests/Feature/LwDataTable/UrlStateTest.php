<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;
use Tests\Fixtures\TestProduct;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (\range(1, 30) as $id) {
        TestProduct::insert([
            'id' => $id,
            'name' => \sprintf('Product %02d', $id),
            'category' => $id % 2 === 0 ? 'even' : 'odd',
            'price' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
});

function urlStateDataTable(array $options = []): DataTable
{
    $dataTable = new DataTable(
        dataSrc: TestProduct::class,
        preset: $options['preset'] ?? 'empty',
        perPage: $options['perPage'] ?? '10,25',
        perPageName: $options['perPageName'] ?? null,
        selectable: $options['selectable'] ?? false,
        stateKey: $options['stateKey'] ?? null,
        rememberState: $options['rememberState'] ?? false,
    );

    $dataTable->search = new Search(new ComponentAttributeBag(['data-fields' => 'name']));
    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => 'Name',
        'data-field' => 'name',
        'sortable' => true,
        'searchable' => true,
    ])));
    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag(['title' => 'Category', 'data-field' => 'category'])));
    $dataTable->addFilter(new ComponentAttributeBag(['data-field' => 'category', 'label' => 'Category', 'mode' => 'exact']));
    $dataTable->addFilter(new ComponentAttributeBag(['data-field' => 'price', 'label' => 'Price', 'input-type' => 'number', 'mode' => 'range']));

    return $dataTable;
}

function rememberingDataTable(string $stateKey = 'products'): DataTable
{
    return urlStateDataTable(['stateKey' => $stateKey, 'rememberState' => true]);
}

// --- Rows per page ---

it('shows the rows per page of the first option', function (string $perPage, string $expected, int $rows) {
    Livewire::test(LwDataTable::class, ['dataTable' => urlStateDataTable(['perPage' => $perPage])])
        ->assertSet('perPage', $expected)
        ->assertSeeHtml('Product ' . \sprintf('%02d', $rows))
        ->assertDontSeeHtml('Product ' . \sprintf('%02d', $rows + 1));
})->with([
    // The select used to show "10" while the table showed the 15 rows of the property's initial value
    '10,25' => ['10,25', '10', 10],
    'max first' => ['max,10', '1000', 30],
    'all first' => ['all,10', DataTable::PER_PAGE_ALL_OPTION_VALUE, 30],
]);

it('shows all the rows with the "all" option, whatever the data source and the pagination', function (string $source, string $pagination) {
    $dataSrc = match ($source) {
        'model' => TestProduct::class,
        'eloquent builder' => TestProduct::query(),
        'query builder' => \Illuminate\Support\Facades\DB::table((new TestProduct())->getTable()),
        'collection' => TestProduct::query()->get()->map->toArray(),
    };

    $dataTable = new DataTable(dataSrc: $dataSrc, perPage: 'all,10', dataSrcPagination: $pagination);
    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag(['title' => 'Name', 'data-field' => 'name'])));

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSet('perPage', DataTable::PER_PAGE_ALL_OPTION_VALUE)
        ->assertSeeHtml('Product 01')
        ->assertSeeHtml('Product 30');
})->with(function () {
    // Collections cannot be cursor paginated
    foreach (['model', 'eloquent builder', 'query builder', 'collection'] as $source) {
        foreach (['length_aware', 'simple', 'cursor'] as $pagination) {
            if ($source !== 'collection' || $pagination !== 'cursor') {
                yield "$source, $pagination" => [$source, $pagination];
            }
        }
    }
});

it('shows an empty table with the "all" option', function () {
    $dataTable = new DataTable(dataSrc: collect(), perPage: 'all');
    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag(['title' => 'Name', 'data-field' => 'name'])));

    Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->assertSee('No data found');
});

it('reads the rows per page from the URL', function () {
    Livewire::withQueryParams(['per-page' => '25'])
        ->test(LwDataTable::class, ['dataTable' => urlStateDataTable()])
        ->assertSet('perPage', '25')
        ->assertSeeHtml('Product 25')
        ->assertDontSeeHtml('Product 26');
});

it('falls back to the first option on rows per page that are not an option', function () {
    Livewire::withQueryParams(['per-page' => '100000'])
        ->test(LwDataTable::class, ['dataTable' => urlStateDataTable()])
        ->assertSet('perPage', '10')
        ->set('perPage', '25')
        ->assertSet('perPage', '25')
        ->set('perPage', '7')
        ->assertSet('perPage', '10')
        ->assertDontSeeHtml('Product 11');
});

it('syncs the rows per page with the URL, leaving the first option out of it', function (?string $perPageName, string $urlParam) {
    $component = Livewire::test(LwDataTable::class, ['dataTable' => urlStateDataTable(['perPageName' => $perPageName])]);

    expect($component->effects['url']['perPage'])->toMatchArray(['as' => $urlParam, 'except' => '10']);
})->with([
    'by default' => [null, 'per-page'],
    'named by the table' => ['rows', 'rows'],
]);

it('takes the name of the rows per page parameter from the preset', function () {
    config()->set('erickcomp-livewire-data-table.presets.per-page-name-test', ['extends' => 'empty', 'query-string-per-page' => 'size']);

    $component = Livewire::test(LwDataTable::class, ['dataTable' => urlStateDataTable(['preset' => 'per-page-name-test'])]);

    expect($component->effects['url']['perPage']['as'])->toBe('size');
});

it('takes the URL parameter names from the preset', function () {
    config()->set('erickcomp-livewire-data-table.presets.url-names-test', [
        'extends' => 'empty',
        'query-string-search' => 'q',
        'query-string-filters' => 'f',
        'query-string-param-cols-search' => 'c',
        'query-string-per-page' => 'n',
    ]);

    $urlEffects = Livewire::test(LwDataTable::class, ['dataTable' => urlStateDataTable(['preset' => 'url-names-test'])])->effects['url'];

    expect(\array_map(fn(array $effect) => $effect['as'], \array_intersect_key($urlEffects, \array_flip(['search', 'filters', 'columnsSearch', 'perPage']))))
        ->toBe(['search' => 'q', 'filters' => 'f', 'columnsSearch' => 'c', 'perPage' => 'n']);
});

it('leaves the empty search, filters and sorting out of the URL', function () {
    $urlEffects = Livewire::test(LwDataTable::class, ['dataTable' => urlStateDataTable()])->effects['url'];

    expect($urlEffects['search']['except'])->toBe('')
        ->and($urlEffects['filters']['except'])->toBe([])
        ->and($urlEffects['columnsSearch']['except'])->toBe([])
        ->and($urlEffects['sortBy']['except'])->toBe('')
        ->and($urlEffects['sortDir']['except'])->toBe('');
});

// --- Remembering the state ---

it('remembers the state in the session', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => rememberingDataTable()])
        ->set('search', 'Product')
        ->call('applyFilters', ['category' => ['category' => 'even'], 'price' => ['price' => ['from' => '5', 'to' => '']]])
        ->set('columnsSearch.name', '1')
        ->call('setSortBy', 'name')
        ->set('perPage', '25')
        ->call('gotoPage', 2, 'page');

    expect(session('lw-dt-state.products'))->toBe([
        'search' => 'Product',
        'filters' => ['category' => ['category' => 'even'], 'price' => ['price' => ['from' => '5']]],
        'columnsSearch' => ['name' => '1'],
        'sortBy' => 'name',
        'sortDir' => 'ASC',
        'perPage' => '25',
    ]);
});

it('remembers nothing without remember-state', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => urlStateDataTable(['stateKey' => 'products'])])
        ->set('search', 'Product');

    expect(session()->has('lw-dt-state.products'))->toBeFalse();
});

it('leaves the page and the selection out of the state', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => urlStateDataTable(['stateKey' => 'products', 'rememberState' => true, 'selectable' => true])])
        ->set('selectedIds', ['1', '2'])
        ->call('gotoPage', 2, 'page');

    expect(\array_keys(session('lw-dt-state.products')))->toBe(['search', 'filters', 'columnsSearch', 'sortBy', 'sortDir', 'perPage']);
});

it('brings the state back when the URL has none of the table parameters', function () {
    session()->put('lw-dt-state.products', [
        'search' => 'Product 1',
        'filters' => ['category' => ['category' => 'even']],
        'columnsSearch' => [],
        'sortBy' => 'name',
        'sortDir' => 'DESC',
        'perPage' => '25',
    ]);

    Livewire::withQueryParams(['unrelated' => 'x'])
        ->test(LwDataTable::class, ['dataTable' => rememberingDataTable()])
        ->assertSet('search', 'Product 1')
        ->assertSet('filters', ['category' => ['category' => 'even']])
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDir', 'DESC')
        ->assertSet('perPage', '25')
        ->assertSeeHtmlInOrder(['Product 18', 'Product 16', 'Product 14', 'Product 12', 'Product 10'])
        ->assertDontSeeHtml('Product 11');
});

it('puts the remembered state in the URL before Livewire reads it', function () {
    session()->put('lw-dt-state.products', [
        'search' => 'Product 1',
        'filters' => ['category' => ['category' => 'even']],
        'columnsSearch' => [],
        'sortBy' => 'name',
        'sortDir' => 'DESC',
        'perPage' => '25',
    ]);

    $query = 'search=Product%201&filters%5Bcategory%5D%5Bcategory%5D=even&sortBy=name&sortDir=DESC&per-page=25';

    Livewire::test(LwDataTable::class, ['dataTable' => rememberingDataTable()])
        ->assertSeeHtml('new URLSearchParams(' . \Illuminate\Support\Js::from($query) . ')')
        ->set('search', 'Product 2')
        ->assertDontSeeHtml('new URLSearchParams(');
});

it('leaves the URL alone when nothing was restored', function (array $state) {
    session()->put('lw-dt-state.products', $state);

    Livewire::test(LwDataTable::class, ['dataTable' => rememberingDataTable()])
        ->assertDontSeeHtml('new URLSearchParams(');
})->with([
    'nothing remembered' => [[]],
    'only defaults remembered' => [['search' => '', 'filters' => [], 'columnsSearch' => [], 'sortBy' => '', 'sortDir' => '', 'perPage' => '10']],
]);

it('lets the URL win over the remembered state', function (array $queryParams) {
    session()->put('lw-dt-state.products', [
        'search' => 'Product 1',
        'filters' => [],
        'columnsSearch' => [],
        'sortBy' => 'name',
        'sortDir' => 'DESC',
        'perPage' => '25',
    ]);

    Livewire::withQueryParams($queryParams)
        ->test(LwDataTable::class, ['dataTable' => rememberingDataTable()])
        ->assertSet('search', $queryParams['search'] ?? '')
        ->assertSet('sortBy', '')
        ->assertSet('perPage', $queryParams['per-page'] ?? '10');
})->with([
    'search' => [['search' => 'Product 2']],
    'rows per page' => [['per-page' => '25']],
    'page' => [['page' => '2']],
    'sorting' => [['sortDir' => 'ASC']],
]);

it('drops what does not fit the table from a remembered state', function () {
    session()->put('lw-dt-state.products', [
        'search' => ['not', 'a', 'string'],
        'filters' => ['category' => ['category' => 'odd', 'other' => 'x'], 'nope' => ['nope' => '1'], 'price' => ['price' => ['from' => ['nested']]]],
        'columnsSearch' => ['name' => '2', 'category' => 'odd', 'price' => ['x']],
        'sortBy' => 'price',
        'sortDir' => 'SIDEWAYS',
        'perPage' => '1000000',
    ]);

    Livewire::test(LwDataTable::class, ['dataTable' => rememberingDataTable()])
        ->assertSet('search', '')
        ->assertSet('filters', ['category' => ['category' => 'odd']])
        ->assertSet('columnsSearch', ['name' => '2'])
        ->assertSet('sortBy', '')
        ->assertSet('sortDir', '')
        ->assertSet('perPage', '10');
});

it('ignores a remembered state that is not an array', function () {
    session()->put('lw-dt-state.products', 'garbage');

    Livewire::test(LwDataTable::class, ['dataTable' => rememberingDataTable()])
        ->assertSet('search', '')
        ->assertSet('perPage', '10');
});

it('keeps the state of each table apart', function () {
    Livewire::test(LwDataTable::class, ['dataTable' => rememberingDataTable('products')])
        ->set('search', 'Product 2');

    Livewire::test(LwDataTable::class, ['dataTable' => rememberingDataTable('archived-products')])
        ->assertSet('search', '')
        ->set('search', 'Product 3');

    Livewire::test(LwDataTable::class, ['dataTable' => rememberingDataTable('products')])
        ->assertSet('search', 'Product 2');

    expect(session('lw-dt-state.archived-products.search'))->toBe('Product 3');
});

it('validates the state attributes', function (array $options, string $message) {
    expect(fn() => urlStateDataTable($options))->toThrow(\InvalidArgumentException::class, $message);
})->with([
    'remember-state without state-key' => [['rememberState' => true], 'A table with [remember-state] needs a [state-key]'],
    'state-key with a dot' => [['stateKey' => 'users.list'], 'Invalid state key: [users.list]'],
    'state-key with a space' => [['stateKey' => 'my users'], 'Invalid state key: [my users]'],
]);
