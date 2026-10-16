<?php

use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
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

function paginatedDataTable(?string $dataSrcPagination): DataTable
{
    $dataTable = new DataTable(
        dataSrc: TestProduct::class,
        preset: 'vanilla',
        dataSrcPagination: $dataSrcPagination,
    );

    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => 'Name',
        'data-field' => 'name',
    ])));

    return $dataTable;
}

/**
 * The table, 2 rows per page
 */
function paginatedTable(?string $dataSrcPagination)
{
    return Livewire::test(LwDataTable::class, ['dataTable' => paginatedDataTable($dataSrcPagination)])
        ->set('perPage', '2');
}

function nextCursor(string $html): string
{
    expect(\preg_match_all("/setPage\\('([^']+)','page'\\)/", $html, $matches))->toBeGreaterThan(0);

    return \end($matches[1]);
}

// --- The attribute ---

it('reads the pagination type from data-src-pagination', function (?string $value, DataSourcePaginationType $expected) {
    expect(paginatedDataTable($value)->dataSrcPaginationType)->toBe($expected);
})->with([
    'none' => ['none', DataSourcePaginationType::None],
    'simple' => ['simple', DataSourcePaginationType::Simple],
    'length aware' => ['length_aware', DataSourcePaginationType::LengthAware],
    'cursor' => ['cursor', DataSourcePaginationType::Cursor],
    'any case' => [' Cursor ', DataSourcePaginationType::Cursor],
    'not given' => [null, DataSourcePaginationType::LengthAware],
    'empty' => ['', DataSourcePaginationType::LengthAware],
]);

it('rejects an unknown pagination type', function () {
    paginatedDataTable('infinite');
})->throws(\ValueError::class, 'Invalid value for the attribute [data-src-pagination]: "infinite". The valid values are: none, simple, length_aware and cursor');

it('hands the pagination type from the Blade attribute over to the data source', function () {
    // More rows than the 15 shown per page by default, so there is a next page to link to
    foreach (\range(7, 20) as $id) {
        TestProduct::insert(['id' => $id, 'name' => "Product $id", 'category' => 'misc', 'price' => $id, 'created_at' => now(), 'updated_at' => now()]);
    }

    $html = Blade::render(
        <<<'BLADE'
        <x-data-table :data-src="Tests\Fixtures\TestProduct::class" data-src-pagination="cursor" preset="vanilla">
            <x-data-table.column title="Name" data-field="name" />
        </x-data-table>
        BLADE,
        deleteCachedView: true,
    );

    // Only cursor pagination links go through setPage() with an encoded cursor
    expect($html)->toMatch("/setPage\\('[^']+','page'\\)/")
        ->not->toContain('gotoPage(');
});

// --- Paging ---

it('pages with length-aware pagination', function () {
    paginatedTable('length_aware')
        ->assertSee(['Laptop Pro', 'Wireless Mouse'])
        ->assertSee('Showing')
        ->call('gotoPage', 2)
        ->assertSee(['Office Desk', 'Ergonomic Chair'])
        ->assertDontSee('Laptop Pro');
});

it('pages with simple pagination', function () {
    paginatedTable('simple')
        ->assertSee(['Laptop Pro', 'Wireless Mouse'])
        ->assertDontSee('Showing')
        ->assertSeeHtml('wire:click="nextPage(\'page\')"')
        ->call('nextPage', 'page')
        ->assertSee(['Office Desk', 'Ergonomic Chair'])
        ->assertDontSee('Laptop Pro');
});

it('pages with cursor pagination', function () {
    $component = paginatedTable('cursor')
        ->assertSee(['Laptop Pro', 'Wireless Mouse'])
        ->assertDontSee('Showing');

    $component->call('setPage', nextCursor($component->html()), 'page')
        ->assertSee(['Office Desk', 'Ergonomic Chair'])
        ->assertDontSee('Laptop Pro');

    $component->call('setPage', nextCursor($component->html()), 'page')
        ->assertSee(['USB Cable', 'Bookshelf'])
        ->assertDontSee('Office Desk');
});

it('lists every row without pagination', function () {
    paginatedTable('none')
        ->assertSee(['Laptop Pro', 'Wireless Mouse', 'Office Desk', 'Ergonomic Chair', 'USB Cable', 'Bookshelf'])
        ->assertDontSee('Showing')
        ->assertDontSeeHtml('nextPage(')
        ->assertDontSeeHtml('setPage(');
});
