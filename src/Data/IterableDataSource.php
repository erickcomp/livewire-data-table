<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;

class IterableDataSource extends StaticDataSource
{
    public function __construct(
        protected iterable $data
    ) {}

    public function getData(LwDataRetrievalParams|null $params = null): iterable
    {
        return $this->data;
    }
}
