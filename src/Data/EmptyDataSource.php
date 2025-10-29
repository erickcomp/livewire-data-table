<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Support\Collection;

class EmptyDataSource implements DataSource
{
    public function __construct() {}

    public function getData(LwDataRetrievalParams|null $params = null): Collection
    {
        return collect();
    }
}
