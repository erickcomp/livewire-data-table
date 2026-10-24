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
    'bootstrap5' => ['bootstrap5', false],
    'simple-bootstrap' => ['simple-bootstrap', true],
    'simple-bootstrap4' => ['simple-bootstrap4', true],
    'simple-bootstrap5' => ['simple-bootstrap5', true],
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

it('points the bootstrap aliases to the bootstrap 5 views', function (string $alias, bool $simple, string $target) {
    expect(renderPaginationView($alias, $simple))->toBe(renderPaginationView($target, $simple));
})->with([
    'bootstrap' => ['bootstrap', false, 'bootstrap5'],
    'simple-bootstrap' => ['simple-bootstrap', true, 'simple-bootstrap5'],
]);

it('renders the bootstrap 5 pagination view without bootstrap 4 only classes', function () {
    $html = renderPaginationView('bootstrap5', false);

    expect($html)
        ->toContain('fw-bold')
        ->toContain('text-body-secondary')
        ->not->toContain('font-weight-bold')
        ->not->toContain('text-muted');
});

it('scrolls to the table only after Livewire has rendered the new page', function (string $view, bool $simple) {
    $html = renderPaginationView($view, $simple);

    // `$wire.<action>()` resolves after the response is applied; a `wire:click` plus an immediate scrollIntoView() on
    // click would aim at the old page and be clamped to its height.
    expect($html)
        ->not->toContain('wire:click=')
        ->toContain('$wire.nextPage(&#039;page&#039;).then(() =&gt; target?.scrollIntoView())')
        ->toContain('closest(&#039;body&#039;)');
})->with([
    'bootstrap4' => ['bootstrap4', false],
    'bootstrap5' => ['bootstrap5', false],
    'simple-bootstrap4' => ['simple-bootstrap4', true],
    'simple-bootstrap5' => ['simple-bootstrap5', true],
    'tailwind3' => ['tailwind3', false],
    'simple-tailwind3' => ['simple-tailwind3', true],
]);

it('does not scroll at all when $scrollTo is false', function () {
    $html = (string) view(DataTable::VIEWS_NAMESPACE . '::bootstrap5', [
        'paginator' => new LengthAwarePaginator(range(1, 2), 30, 2, 2),
        'elements' => [[1 => '#', 2 => '#', 3 => '#']],
        'scrollTo' => false,
    ])->render();

    expect($html)
        ->toContain('x-on:click="$wire.nextPage(&#039;page&#039;)"')
        ->not->toContain('scrollIntoView');
});
