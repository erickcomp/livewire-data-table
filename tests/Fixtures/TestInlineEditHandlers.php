<?php

namespace Tests\Fixtures;

use ErickComp\LivewireDataTable\Exceptions\ErrorMessageForUserException;
use Illuminate\Validation\ValidationException;

/**
 * Inline edit handlers for the tests, acting on TestProduct
 */
class TestInlineEditHandlers
{
    /** @var array<int, array<string, mixed>> */
    public static array $calls = [];

    public function save(TestProduct $product, array $values, string $id): void
    {
        static::$calls[] = ['id' => $id, 'values' => $values, 'row' => $product->name];

        $product->update($values);
    }

    public function record(mixed $row, array $values, string $id): void
    {
        static::$calls[] = ['id' => $id, 'values' => $values, 'row' => \data_get($row, 'name')];
    }

    public function message(TestProduct $product, array $values): string
    {
        $product->update($values);

        return "{$product->name} saved";
    }

    public function rejectName(): void
    {
        throw ValidationException::withMessages(['name' => 'This name is taken.']);
    }

    public function fail(): void
    {
        throw new ErrorMessageForUserException('The product is locked');
    }
}
