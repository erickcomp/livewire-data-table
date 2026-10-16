<?php

namespace Workbench\App\DataTable\Products;

use Tests\Fixtures\TestProduct;

/**
 * The demo's "Delete" row action
 */
class DeleteProduct
{
    public function __invoke(TestProduct $product): string
    {
        $product->delete();

        return "{$product->name} deleted";
    }
}
