<?php

namespace ErickComp\LivewireDataTable;

use Livewire\ImplicitlyBoundMethod;

class ServerExecutor
{
    public static function call($callable, ...$params)
    {
        return ImplicitlyBoundMethod::call(app(), $callable, $params);
    }
}
