<?php
namespace ErickComp\LivewireDataTable\Data;

enum DataSourcePaginationType: string
{
    case None = 'none';
    case Simple = 'simple';
    case LengthAware = 'length_aware';
    case Cursor = 'cursor';

    public static function values(): array
    {
        return \array_map(fn(self $case): string => $case->value, self::cases());
    }

    public static function fromIgnoreCase(string $value): self
    {
        return self::from(\strtolower($value));
    }

    public static function tryFromIgnoreCase(string $value): ?self
    {
        return self::tryFrom(\strtolower($value));
    }
}
