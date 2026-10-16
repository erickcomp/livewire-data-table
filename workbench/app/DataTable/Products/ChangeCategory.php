<?php

namespace Workbench\App\DataTable\Products;

use ErickComp\LivewireDataTable\Selection\Selection;
use ErickComp\LivewireDataTable\Support\ActionResult;

/**
 * The demo's "Move to" bulk action, with a category per option
 */
class ChangeCategory
{
    public function handle(Selection $selection, string $option): ActionResult
    {
        $selection->query()->update(['category' => $option]);

        return ActionResult::success(":count product(s) moved to $option");
    }
}
