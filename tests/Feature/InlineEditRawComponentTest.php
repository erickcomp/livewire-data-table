<?php

use Illuminate\Support\Facades\Blade;

it('builds the inline editing from the attributes of the table and of the columns', function () {
    $html = Blade::render(
        <<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Laptop', 'price' => 10]])" preset="empty"
            inline-edit="Tests\Fixtures\TestInlineEditHandlers@record" inline-edit-trigger="row-click">
            <x-data-table.column title="Name" data-field="name" editor="text" editor-rules="required|max:30" editor-maxlength="30" td-class="name-td" />
            <x-data-table.column title="Price" data-field="price" editor="number" :editor-rules="['required', 'min:0']" editor-field="price">
                <td>$ {{ $__row['price'] }}</td>
            </x-data-table.column>
        </x-data-table>
        BLADE,
        deleteCachedView: true,
    );

    expect($html)->toContain('<tr data-lw-dt-row-id="1" data-lw-dt-row-click-edit="" x-on:click="onRowClick($event)"')
        ->toContain('<td class="name-td">')
        ->toContain('<td>$ 10</td>')
        // The editor attributes stay off the cells
        ->not->toContain('editor')
        ->not->toContain('maxlength');
});

it('reports editors without a handler when the table is rendered', function () {
    try {
        Blade::render(
            <<<'BLADE'
            <x-data-table :data-src="collect([['id' => 1, 'name' => 'Laptop']])" preset="empty">
                <x-data-table.column title="Name" data-field="name" editor="text" />
            </x-data-table>
            BLADE,
            deleteCachedView: true,
        );
    } catch (\Throwable $error) {
        while (!$error instanceof \LogicException && $error->getPrevious() !== null) {
            $error = $error->getPrevious();
        }
    }

    expect($error ?? null)->toBeInstanceOf(\LogicException::class)
        ->and($error->getMessage())->toBe('The column [Name] has an [editor], but the table has no [inline-edit] handler to save the changes');
});
