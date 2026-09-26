<?php

use ErickComp\LivewireDataTable\DataTable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

function renderPaginationView(string $view, bool $simple, int $currentPage = 2): string
{
    $items = range(1, 10);

    $paginator = $simple
        ? new Paginator(array_slice($items, 0, 3), 2, $currentPage)
        : new LengthAwarePaginator(array_slice($items, 0, 2), 30, 2, $currentPage);

    return (string) $paginator->render(DataTable::VIEWS_NAMESPACE . '::' . $view);
}

dataset('pagination views', [
    'bootstrap' => ['bootstrap', false],
    'bootstrap4' => ['bootstrap4', false],
    'simple-bootstrap' => ['simple-bootstrap', true],
    'simple-bootstrap4' => ['simple-bootstrap4', true],
    'tailwind' => ['tailwind', false],
    'tailwind3' => ['tailwind3', false],
    'simple-tailwind' => ['simple-tailwind', true],
    'simple-tailwind3' => ['simple-tailwind3', true],
]);

it('renders pagination views as blade templates', function (string $view, bool $simple) {
    $html = renderPaginationView($view, $simple);

    expect($html)
        ->not->toContain('@if')
        ->not->toContain('@php')
        ->not->toContain('{!!')
        ->not->toContain('{{');
})->with('pagination views');

it('translates pagination views without leaking translation keys', function (string $locale, string $view, bool $simple) {
    app()->setLocale($locale);

    $html = renderPaginationView($view, $simple);

    expect($html)
        ->not->toContain('erickcomp_lw_data_table::')
        ->toContain(e(__('erickcomp_lw_data_table::messages.pagination.previous')))
        ->toContain(e(__('erickcomp_lw_data_table::messages.pagination.next')));
})->with(['en', 'pt_BR'])->with('pagination views');

it('uses the translated labels for previous and next pagination links', function () {
    app()->setLocale('pt_BR');

    $html = renderPaginationView('tailwind3', false);

    expect($html)
        ->toContain('Página anterior')
        ->toContain('Próxima página')
        ->toContain('Navegação da Paginação')
        ->not->toContain('Pagination Navigation');
});
