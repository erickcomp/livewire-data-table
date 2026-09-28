<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\CustomRenderedColumn;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;

/**
 * The opening <td> tags rendered inside the table body, in document order.
 *
 * @return string[]
 */
function tbodyOpeningTdTags(string $html): array
{
    \preg_match('/<tbody\b.*<\/tbody>/s', $html, $tbody);
    \preg_match_all('/<td\b[^>]*>/', $tbody[0] ?? '', $tds);

    return $tds[0];
}

function tdAttributesTestDataTable(array $rows): DataTable
{
    config()->set('erickcomp-livewire-data-table.presets.td-attributes-test', [
        'extends' => 'empty',
        'table' => ['tbody' => ['tr' => ['td' => ['class' => ['preset-td', 'preset-td-2']]]]],
    ]);

    return new DataTable(
        dataSrc: collect($rows),
        preset: 'td-attributes-test',
        paginationView: 'bootstrap',
    );
}

it('renders the td attributes of data and custom rendered columns on every row', function () {
    $dataTable = tdAttributesTestDataTable([['id' => 1, 'name' => 'Alpha'], ['id' => 2, 'name' => 'Beta']]);

    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => 'Name',
        'data-field' => 'name',
        'class' => 'col-class',
        'style' => 'color: red',
        'td-class' => 'td-only',
        'td-style' => 'font-weight: bold',
        'data-extra' => 'x',
        'td-data-cell' => 'y',
        'th-class' => 'th-only',
    ])));

    $dataTable->columns->push(DataColumn::fromComponentAttributeBag(new ComponentAttributeBag([
        'title' => 'Id',
        'data-field' => 'id',
    ])));

    $dataTable->columns->push(CustomRenderedColumn::fromComponentAttributeBag(
        new ComponentAttributeBag([
            'title' => 'Actions',
            'data-field' => 'id',
            'class' => 'actions',
            'td-style' => 'width: 10px',
            'td-data-role' => 'actions',
        ]),
        customRendererCode: '<a href="/edit/{{ $__row[\'id\'] }}">Edit</a>',
    ));

    $dataTable->columns->push(CustomRenderedColumn::fromComponentAttributeBag(
        new ComponentAttributeBag(['title' => 'Own', 'td-class' => 'own-td', 'aria-label' => 'own']),
        customRendererCode: '<td {{ $attributes->merge([\'data-own\' => \'yes\']) }}>Own {{ $__row[\'id\'] }}</td>',
    ));

    $html = Livewire::test(LwDataTable::class, ['data-table' => $dataTable])->html();

    $rowTds = [
        '<td style="color: red; font-weight: bold;" class="preset-td preset-td-2 col-class td-only" data-cell="y" data-extra="x">',
        '<td class="preset-td preset-td-2">',
        '<td style="width: 10px;" class="preset-td preset-td-2 actions" data-role="actions" >',
        '<td data-own="yes" class="preset-td preset-td-2 own-td" aria-label="own">',
    ];

    expect(tbodyOpeningTdTags($html))->toBe([...$rowTds, ...$rowTds]);
});

it('does not leak attributes a custom renderer mutates on one row into the next rows', function () {
    $dataTable = tdAttributesTestDataTable([['id' => 1], ['id' => 2], ['id' => 3]]);

    // The renderer's content is not a <td>: the view wraps it in a <td> built from the very bag the
    // renderer received, so the mutation shows on that row's cell — and only there.
    $dataTable->columns->push(CustomRenderedColumn::fromComponentAttributeBag(
        new ComponentAttributeBag(['title' => 'Wrapped', 'class' => 'wrapped', 'data-kind' => 'wrapped']),
        customRendererCode: <<<'BLADE'
            @php
                if ($__row['id'] === 1) {
                    $attributes['data-mutated'] = 'row-1';
                    $attributes['class'] = 'replaced';
                }
            @endphp
            Cell {{ $__row['id'] }}
            BLADE,
    ));

    // The renderer outputs its own <td> from the mutated bag.
    $dataTable->columns->push(CustomRenderedColumn::fromComponentAttributeBag(
        new ComponentAttributeBag(['title' => 'Own', 'td-class' => 'own']),
        customRendererCode: <<<'BLADE'
            @php
                if ($__row['id'] === 1) {
                    $attributes['data-own-mutated'] = 'yes';
                }
            @endphp
            <td {{ $attributes }}>{{ $__row['id'] }}</td>
            BLADE,
    ));

    $html = Livewire::test(LwDataTable::class, ['data-table' => $dataTable])->html();

    expect(tbodyOpeningTdTags($html))->toBe([
        '<td class="replaced" data-kind="wrapped" data-mutated="row-1" >',
        '<td class="preset-td preset-td-2 own" data-own-mutated="yes">',
        '<td class="preset-td preset-td-2 wrapped" data-kind="wrapped" >',
        '<td class="preset-td preset-td-2 own">',
        '<td class="preset-td preset-td-2 wrapped" data-kind="wrapped" >',
        '<td class="preset-td preset-td-2 own">',
    ]);
});
