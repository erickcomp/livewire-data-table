<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('registers scripts pushed via x-data-table.scripts onto the rendered table', function () {
    $rendered = Blade::render(
        <<<'BLADE'
        <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
            <x-data-table.column title="Name" data-field="name" />

            <x-data-table.scripts>
                <script>window.__lwDtScriptsProbe = true;</script>
            </x-data-table.scripts>
        </x-data-table>
        BLADE,
        deleteCachedView: true,
    );

    expect($rendered)->toContain('window.__lwDtScriptsProbe = true;');
});

it('throws when x-data-table.scripts is not nested inside x-data-table', function () {
    $exception = null;

    try {
        Blade::render(
            <<<'BLADE'
            <x-data-table.scripts>
                <script>window.__nope = true;</script>
            </x-data-table.scripts>
            BLADE,
            deleteCachedView: true,
        );
    } catch (\Throwable $e) {
        $exception = $e;
    }

    expect($exception)->toBeInstanceOf(ViewException::class);
    expect($exception->getPrevious())->toBeInstanceOf(\LogicException::class);
    expect($exception->getPrevious()->getMessage())
        ->toBe('You can only use the [x-data-table.scripts] as a direct child of the [x-data-table] component');
});

it('throws a clean guard exception (not a ParseError) when scripts is nested inside a custom-rendered column', function () {
    $exception = null;

    try {
        Blade::render(
            <<<'BLADE'
            <x-data-table :data-src="collect([['id' => 1, 'name' => 'Product']])" preset="empty">
                <x-data-table.column title="Name" data-field="name">
                    <x-data-table.scripts>
                        <script>window.__nope = true;</script>
                    </x-data-table.scripts>
                </x-data-table.column>
            </x-data-table>
            BLADE,
            deleteCachedView: true,
        );
    } catch (\Throwable $e) {
        $exception = $e;
    }

    // The column's custom renderer is compiled and rendered as its own nested Blade
    // pass, so the guard's LogicException may be wrapped by more than one ViewException
    // as it bubbles up. Walk the chain to the root cause rather than assuming one level.
    $root = $exception;
    while ($root !== null && !($root instanceof \LogicException)) {
        expect($root)->toBeInstanceOf(ViewException::class);
        $root = $root->getPrevious();
    }

    expect($root)->toBeInstanceOf(\LogicException::class);
    expect($root->getMessage())
        ->toBe('You can only use the [x-data-table.scripts] as a direct child of the [x-data-table] component');
});
