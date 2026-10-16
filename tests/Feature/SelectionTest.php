<?php

use ErickComp\LivewireDataTable\Data\DataSource;
use ErickComp\LivewireDataTable\Data\DataSourceFactory;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use ErickComp\LivewireDataTable\Selection\Selection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;
use Tests\Fixtures\TestProduct;
use Tests\Fixtures\TestSelectionDataProvider;

uses(RefreshDatabase::class);

beforeEach(function () {
    TestProduct::insert(\array_map(
        fn(array $row) => $row + ['created_at' => now(), 'updated_at' => now()],
        TestSelectionDataProvider::ROWS,
    ));
});

function selectionDataSource(mixed $dataSrc): DataSource
{
    return DataSourceFactory::new()->make($dataSrc, DataSourcePaginationType::LengthAware);
}

/**
 * Params as the table hands them over: 2 rows per page (so a source that only gives one page shows up), sorted by
 * id unless told otherwise, with a global search over the product name.
 */
function selectionParams(array $overrides = []): LwDataRetrievalParams
{
    $dataTable = new DataTable();
    $dataTable->search = new Search(new ComponentAttributeBag(['data-fields' => 'name']));

    return new LwDataRetrievalParams(
        page: 1,
        perPage: '2',
        pageName: 'page',
        search: $overrides['search'] ?? null,
        columnsSearch: $overrides['columnsSearch'] ?? [],
        filters: $overrides['filters'] ?? [],
        sortBy: $overrides['sortBy'] ?? 'id',
        sortDir: $overrides['sortDir'] ?? 'ASC',
        collectionsSortingFlags: SORT_NATURAL | SORT_FLAG_CASE,
        dataTable: $dataTable,
    );
}

function makeTestSelection(DataSource $dataSource, string $mode, array $ids = [], array $paramsOverrides = []): Selection
{
    return new Selection($dataSource, selectionParams($paramsOverrides), 'id', $mode, $ids);
}

function furnitureFilter(): array
{
    return ['column' => 'category', 'mode' => Filter::MODE_EXACT, 'value' => 'furniture', 'type' => Filter::TYPE_TEXT];
}

dataset('selection data sources', [
    'Eloquent model' => fn() => selectionDataSource(TestProduct::class),
    'Eloquent builder' => fn() => selectionDataSource(TestProduct::query()),
    'query builder' => fn() => selectionDataSource(DB::table('test_products')),
    'collection' => fn() => selectionDataSource(collect(TestSelectionDataProvider::ROWS)),
    'callable returning a builder' => fn() => selectionDataSource(TestSelectionDataProvider::class . '@builder'),
    'callable returning a paginated builder' => fn() => selectionDataSource(TestSelectionDataProvider::class . '@paginatedBuilder'),
    'callable returning a collection' => fn() => selectionDataSource(TestSelectionDataProvider::class . '@collection'),
    'callable returning a paginated collection' => fn() => selectionDataSource(TestSelectionDataProvider::class . '@paginatedCollection'),
]);

dataset('query-backed data sources', [
    'Eloquent model' => fn() => selectionDataSource(TestProduct::class),
    'Eloquent builder' => fn() => selectionDataSource(TestProduct::query()),
    'query builder' => fn() => selectionDataSource(DB::table('test_products')),
    'callable returning a builder' => fn() => selectionDataSource(TestSelectionDataProvider::class . '@builder'),
]);

dataset('collection-backed data sources', [
    'collection' => fn() => selectionDataSource(collect(TestSelectionDataProvider::ROWS)),
    'callable returning a collection' => fn() => selectionDataSource(TestSelectionDataProvider::class . '@collection'),
    'callable returning a paginated builder' => fn() => selectionDataSource(TestSelectionDataProvider::class . '@paginatedBuilder'),
]);

// --- Resolving the selection ---

it('resolves the selected ids, leaving out the ones that are not among the rows', function (DataSource $dataSource) {
    $selection = makeTestSelection($dataSource, Selection::MODE_IDS, ['3', '1', '999']);

    expect($selection->ids())->toBe(['1', '3'])
        ->and($selection->count())->toBe(2)
        ->and($selection->isEmpty())->toBeFalse()
        ->and(collect($selection->rows())->pluck('name')->all())->toBe(['Laptop Pro', 'Office Desk']);
})->with('selection data sources');

it('resolves every row but the excluded ones in the "all" mode', function (DataSource $dataSource) {
    $selection = makeTestSelection($dataSource, Selection::MODE_ALL, ['2', '5']);

    expect($selection->ids())->toBe(['1', '3', '4', '6'])
        ->and($selection->count())->toBe(4)
        ->and(collect($selection->rows())->pluck('id')->map(fn($id) => (string) $id)->all())->toBe(['1', '3', '4', '6']);
})->with('selection data sources');

it('keeps the selection within the current filters', function (DataSource $dataSource) {
    $filters = ['filters' => [furnitureFilter()]];

    expect(makeTestSelection($dataSource, Selection::MODE_IDS, ['1', '3', '4'], $filters)->ids())->toBe(['3', '4'])
        ->and(makeTestSelection($dataSource, Selection::MODE_ALL, ['4'], $filters)->ids())->toBe(['3', '6'])
        ->and(makeTestSelection($dataSource, Selection::MODE_ALL, [], $filters)->count())->toBe(3);
})->with('selection data sources');

it('keeps the selection within the current search and columns search', function (DataSource $dataSource) {
    expect(makeTestSelection($dataSource, Selection::MODE_ALL, [], ['search' => 'mouse'])->ids())->toBe(['2'])
        ->and(makeTestSelection($dataSource, Selection::MODE_ALL, [], ['columnsSearch' => ['name' => 'chair']])->ids())->toBe(['4'])
        ->and(makeTestSelection($dataSource, Selection::MODE_IDS, ['1', '2'], ['search' => 'mouse'])->ids())->toBe(['2']);
})->with('selection data sources');

it('keeps the table sorting', function (DataSource $dataSource) {
    $selection = makeTestSelection($dataSource, Selection::MODE_ALL, [], ['sortBy' => 'price', 'sortDir' => 'DESC']);

    $byPriceDesc = ['1', '4', '3', '6', '2', '5'];

    expect($selection->ids())->toBe($byPriceDesc)
        ->and($selection->count())->toBe(6)
        ->and($selection->lazy(2)->pluck('id')->map(fn($id) => (string) $id)->all())->toBe($byPriceDesc);
})->with('selection data sources');

it('iterates the rows by id with lazyById()', function (DataSource $dataSource) {
    $selection = makeTestSelection($dataSource, Selection::MODE_ALL, ['3'], ['sortBy' => 'price', 'sortDir' => 'DESC']);

    $ids = $selection->lazyById(2)->pluck('id')->map(fn($id) => (string) $id)->all();

    expect($selection->lazyById(2))->toBeInstanceOf(LazyCollection::class)
        ->and(collect($ids)->sort()->values()->all())->toBe(['1', '2', '4', '5', '6']);
})->with('selection data sources');

it('selects nothing from an empty "ids" selection, without going to the data source', function () {
    DB::enableQueryLog();

    $selection = makeTestSelection(selectionDataSource(TestProduct::class), Selection::MODE_IDS, []);

    expect($selection->isEmpty())->toBeTrue()
        ->and($selection->count())->toBe(0)
        ->and($selection->ids())->toBe([])
        ->and($selection->rows())->toHaveCount(0)
        ->and($selection->lazy()->all())->toBe([])
        ->and(DB::getQueryLog())->toBe([]);
});

it('selects nothing when none of the given ids is among the rows', function (DataSource $dataSource) {
    $selection = makeTestSelection($dataSource, Selection::MODE_IDS, ['998', '999']);

    expect($selection->isEmpty())->toBeTrue()
        ->and($selection->ids())->toBe([])
        ->and($selection->rows())->toHaveCount(0);
})->with('selection data sources');

it('counts the rows of a grouped query like the paginator does', function () {
    $grouped = DB::table('test_products')
        ->select('category', DB::raw('min(id) as id'))
        ->groupBy('category');

    $selection = makeTestSelection(selectionDataSource($grouped), Selection::MODE_ALL, [], ['sortBy' => '']);

    expect($selection->count())->toBe(2)
        ->and($selection->ids())->toBe(['1', '3']);
});

// --- Data sources ---

it('hands over every matching row from a paginating callable, whatever params it is given', function () {
    $dataSource = selectionDataSource(TestSelectionDataProvider::class . '@paginatedBuilder');

    $rows = $dataSource->getUnpaginatedData(selectionParams(['filters' => [furnitureFilter()]]));

    expect(collect($rows)->pluck('name')->all())->toBe(['Office Desk', 'Ergonomic Chair', 'Bookshelf'])
        ->and($dataSource->getData(selectionParams())->items())->toHaveCount(2);
});

// --- Queries ---

it('builds a query for the selected rows when the data source is backed by one', function (DataSource $dataSource) {
    $selection = makeTestSelection($dataSource, Selection::MODE_IDS, ['2', '4']);

    expect($selection->isQueryable())->toBeTrue()
        ->and($selection->query())->toBeInstanceOf($dataSource instanceof \ErickComp\LivewireDataTable\Data\QueryBuilderDataSource ? QueryBuilder::class : EloquentBuilder::class)
        ->and($selection->query()->pluck('name')->all())->toBe(['Wireless Mouse', 'Ergonomic Chair']);
})->with('query-backed data sources');

it('does not build a query when the data source is not backed by one', function (DataSource $dataSource) {
    $selection = makeTestSelection($dataSource, Selection::MODE_ALL);

    expect($selection->isQueryable())->toBeFalse()
        ->and(fn() => $selection->query())->toThrow(\LogicException::class, 'Cannot build a query for the selected rows');
})->with('collection-backed data sources');

it('hands out a new query every time', function () {
    $selection = makeTestSelection(selectionDataSource(TestProduct::query()), Selection::MODE_IDS, ['1']);

    $selection->query()->where('category', 'furniture');

    expect($selection->ids())->toBe(['1']);
});

it('qualifies the identity column on Eloquent queries', function () {
    $selection = makeTestSelection(selectionDataSource(TestProduct::class), Selection::MODE_ALL, ['1']);

    expect($selection->query()->toSql())->toContain('"test_products"."id" not in');
});

it('drops ids that cannot be the integer key of an Eloquent model', function () {
    $selection = makeTestSelection(selectionDataSource(TestProduct::class), Selection::MODE_IDS, ['1', 'abc', '1 OR 1=1', '3']);

    expect($selection->query()->getBindings())->toBe(['1', '3'])
        ->and($selection->ids())->toBe(['1', '3']);
});

it('keeps non-numeric ids for query builders, which know nothing about the column type', function () {
    $selection = makeTestSelection(selectionDataSource(DB::table('test_products')), Selection::MODE_IDS, ['1', 'abc']);

    expect($selection->query()->getBindings())->toBe(['1', 'abc'])
        ->and($selection->ids())->toBe(['1']);
});

// --- State ---

it('normalizes the given ids to unique non-empty strings', function () {
    $stringable = new class implements \Stringable {
        public function __toString(): string
        {
            return 'x7';
        }
    };

    expect(Selection::normalizeIds([1, '1', '', null, true, ['x'], new \stdClass(), 2.5, '2', $stringable]))
        ->toBe(['1', '2.5', '2', 'x7']);
});

it('exposes the mode and the given ids', function () {
    $dataSource = selectionDataSource(TestProduct::class);

    $ids = makeTestSelection($dataSource, Selection::MODE_IDS, [1, '2', '2']);
    $all = makeTestSelection($dataSource, Selection::MODE_ALL, ['5']);

    expect($ids->mode())->toBe(Selection::MODE_IDS)
        ->and($ids->isAllMatching())->toBeFalse()
        ->and($ids->selectedIds())->toBe(['1', '2'])
        ->and($ids->excludedIds())->toBe([])
        ->and($ids->toArray())->toBe(['mode' => 'ids', 'ids' => ['1', '2']])
        ->and($all->mode())->toBe(Selection::MODE_ALL)
        ->and($all->isAllMatching())->toBeTrue()
        ->and($all->selectedIds())->toBe([])
        ->and($all->excludedIds())->toBe(['5'])
        ->and($all->identityColumn())->toBe('id');
});

it('rejects an unknown mode', function () {
    makeTestSelection(selectionDataSource(TestProduct::class), 'some');
})->throws(\ValueError::class, 'Invalid selection mode: [some]');

// --- LwDataRetrievalParams::withoutPagination() ---

it('returns every row in a single page once pagination is disabled', function () {
    $params = selectionParams();
    $unpaginated = $params->withoutPagination();

    expect($params->isPaginationDisabled())->toBeFalse()
        ->and($unpaginated->isPaginationDisabled())->toBeTrue()
        ->and($params->paginate(TestProduct::query())->items())->toHaveCount(2)
        ->and($unpaginated->paginate(TestProduct::query()))->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($unpaginated->paginate(TestProduct::query())->items())->toHaveCount(6)
        ->and($unpaginated->paginate(TestProduct::query())->hasMorePages())->toBeFalse()
        ->and($unpaginated->paginate(DB::table('test_products'))->items())->toHaveCount(6)
        ->and($unpaginated->paginate(collect(TestSelectionDataProvider::ROWS))->items())->toHaveCount(6)
        ->and($unpaginated->simplePaginate(TestProduct::query()))->toBeInstanceOf(Paginator::class)
        ->and($unpaginated->simplePaginate(TestProduct::query())->items())->toHaveCount(6)
        ->and($unpaginated->simplePaginate(TestProduct::query())->hasMorePages())->toBeFalse()
        ->and($unpaginated->simplePaginate(collect(TestSelectionDataProvider::ROWS))->items())->toHaveCount(6)
        ->and($unpaginated->cursorPaginate(TestProduct::query()))->toBeInstanceOf(CursorPaginator::class)
        ->and($unpaginated->cursorPaginate(TestProduct::query())->items())->toHaveCount(6)
        ->and($unpaginated->applyAndPaginate(TestProduct::query())->items())->toHaveCount(6);
});

it('paginates a query builder', function () {
    expect(selectionParams()->paginate(DB::table('test_products'))->items())->toHaveCount(2);
});

// --- LwDataTable ---

it('resolves the selection with the filters in place, even before the table renders', function () {
    $dataTable = new DataTable(dataSrc: TestProduct::class, preset: 'empty');
    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag(['title' => 'Name', 'data-field' => 'name'])));
    $dataTable->addFilter(new ComponentAttributeBag(['data-field' => 'category', 'label' => 'Category', 'mode' => 'exact']));

    $component = Livewire::test(LwDataTable::class, ['dataTable' => $dataTable])
        ->set('filters', ['category' => ['category' => 'furniture']]);

    $table = \Livewire\invade($component->instance());

    // As in a request that calls an action: render() has not processed the filters yet
    $table->processedFilters = [];

    expect($table->makeSelection(Selection::MODE_ALL, ['4'])->ids())->toBe(['3', '6'])
        ->and($table->makeSelection(Selection::MODE_IDS, ['1', '3'])->ids())->toBe(['3']);
});
