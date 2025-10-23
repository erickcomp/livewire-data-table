<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;

class EmptyDataSource extends StaticDataSource
{
    public function __construct() {}

    public function getData(LwDataRetrievalParams|null $params = null): array
    {
        return [];
    }
}
