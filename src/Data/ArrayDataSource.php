<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;

class ArrayDataSource extends IterableDataSource
{
    public function __construct(
        protected array $data
    ) {}

    public function getData(LwDataRetrievalParams|null $params = null): array
    {
        return $this->data;
    }
}
