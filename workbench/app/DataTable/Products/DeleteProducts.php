<?php

namespace Workbench\App\DataTable\Products;

use ErickComp\LivewireDataTable\Selection\Selection;

/**
 * The demo's "Delete" bulk action
 */
class DeleteProducts
{
    public function __invoke(Selection $selection): string
    {
        $deleted = $selection->query()->delete();

        return "$deleted product(s) deleted";
    }
}
