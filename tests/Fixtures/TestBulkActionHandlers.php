<?php

namespace Tests\Fixtures;

use ErickComp\LivewireDataTable\DataTable\BulkAction;
use ErickComp\LivewireDataTable\Exceptions\ErrorMessageForUserException;
use ErickComp\LivewireDataTable\Selection\Selection;
use ErickComp\LivewireDataTable\Support\ActionResult;

/**
 * Bulk action handlers for the tests, acting on TestProduct
 */
class TestBulkActionHandlers
{
    /** @var array<int, array{ids: string[], option: ?string, action: string}> */
    public static array $calls = [];

    public function delete(Selection $selection): string
    {
        $deleted = $selection->query()->delete();

        return "$deleted product(s) deleted";
    }

    public function changeCategory(Selection $selection, string $option): ActionResult
    {
        $selection->query()->update(['category' => $option]);

        return ActionResult::info(":count product(s) moved to $option");
    }

    public function record(Selection $selection, ?string $option, BulkAction $action): void
    {
        $ids = $selection->ids();
        \sort($ids);

        static::$calls[] = ['ids' => $ids, 'option' => $option, 'action' => $action->name];
    }

    public function failWithException(): void
    {
        throw new ErrorMessageForUserException('The products are locked');
    }

    public function failWithResult(): ActionResult
    {
        return ActionResult::error('Some products could not be changed');
    }

    public function redirect(): mixed
    {
        return redirect()->to('/products/done');
    }

    public function download(Selection $selection): mixed
    {
        return response()->streamDownload(function () use ($selection) {
            echo \implode(',', $selection->rows()->pluck('name')->all());
        }, 'products.csv');
    }
}
