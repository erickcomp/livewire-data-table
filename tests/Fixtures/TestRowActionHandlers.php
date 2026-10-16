<?php

namespace Tests\Fixtures;

use ErickComp\LivewireDataTable\DataTable\RowAction;
use ErickComp\LivewireDataTable\Exceptions\ErrorMessageForUserException;
use ErickComp\LivewireDataTable\Selection\Selection;

/**
 * Row action handlers for the tests, acting on TestProduct
 */
class TestRowActionHandlers
{
    /** @var array<int, array<string, mixed>> */
    public static array $calls = [];

    public function delete(TestProduct $product): string
    {
        $product->delete();

        return "{$product->name} deleted";
    }

    public function record(mixed $row, string $id, Selection $selection, RowAction $action): void
    {
        static::$calls[] = [
            'row' => \data_get($row, 'name'),
            'id' => $id,
            'selectionIds' => $selection->ids(),
            'action' => $action->name,
        ];
    }

    public function fail(): void
    {
        throw new ErrorMessageForUserException('The product is locked');
    }

    public function redirect(TestProduct $product): mixed
    {
        return redirect()->to("/products/{$product->id}/done");
    }
}
