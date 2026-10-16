<?php

namespace Tests\Fixtures;

class TestStaticMagicCallables
{
    public static function __callStatic(string $method, array $arguments): string
    {
        return "magic-static:$method:" . \count($arguments);
    }
}
