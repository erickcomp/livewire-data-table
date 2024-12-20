<?php

namespace ErickComp\LivewireDataTable\Src\Drawer;

class DataTableActionResponse
{
    public function __construct(
        public readonly bool $isOk,
        public readonly string $message = '',
    ) {}
}
