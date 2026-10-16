<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Tests\Fixtures\TestProduct;

class DatabaseSeeder extends Seeder
{
    public const PRODUCTS_COUNT = 120;

    public const CATEGORIES = ['Books', 'Electronics', 'Garden', 'Toys'];

    /**
     * Deterministic data, so browser tests can assert on it: product names are a permutation of the
     * ids (product #1 is "Product 038"), so sorting by name changes the order of the rows.
     */
    public function run(): void
    {
        $createdAt = Carbon::parse('2026-01-01 12:00:00');
        $rows = [];

        for ($i = 1; $i <= static::PRODUCTS_COUNT; $i++) {
            $rows[] = [
                'id' => $i,
                'name' => 'Product ' . \sprintf('%03d', (($i * 37) % static::PRODUCTS_COUNT) + 1),
                'category' => static::CATEGORIES[$i % \count(static::CATEGORIES)],
                'price' => \number_format(\fmod($i * 7.5, 500) + 9.9, 2, '.', ''),
                'created_at' => $createdAt->copy()->addDays($i),
                'updated_at' => $createdAt->copy()->addDays($i),
            ];
        }

        TestProduct::query()->insert($rows);
    }
}
