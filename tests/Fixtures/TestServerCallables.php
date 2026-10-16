<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;

/**
 * Targets for the ServerCallable tests: each method reports how it was called
 */
class TestServerCallables
{
    public function __construct(protected ?Config $config = null) {}

    public static function staticMethod(string $option, ?Collection $rows = null): string
    {
        return "static:$option:" . ($rows?->count() ?? 'none');
    }

    public function instanceMethod(string $option): string
    {
        return "instance:$option:" . ($this->config !== null ? 'made-by-container' : 'made-without-container');
    }

    public function __invoke(string $option): string
    {
        return "invoked:$option";
    }

    public function __call(string $method, array $arguments): string
    {
        return "magic-instance:$method:" . \count($arguments);
    }
}
