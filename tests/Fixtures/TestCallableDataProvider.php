<?php

namespace Tests\Fixtures;

use Illuminate\Support\Collection;

class TestCallableDataProvider
{
    public static function getStaticData(): Collection
    {
        return collect([
            ['id' => 1, 'name' => 'Item A'],
            ['id' => 2, 'name' => 'Item B'],
            ['id' => 3, 'name' => 'Item C'],
        ]);
    }

    public function getData(): Collection
    {
        return static::getStaticData();
    }
}
