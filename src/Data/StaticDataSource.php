<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Support\Collection;

class StaticDataSource implements DataSource
{
    public function __construct(
        protected mixed $data,
    ) {}
    public function getData(LwDataRetrievalParams|null $params = null): iterable|Collection
    {
        return $this->data;
    }
}
