<?php

namespace Workbench\App\DataTable\Products;

use Tests\Fixtures\TestProduct;

/**
 * Saves a product edited inline in the demo
 */
class SaveProduct
{
    public function __invoke(TestProduct $product, array $values): string
    {
        $product->update($values);

        return "{$product->name} saved";
    }
}
