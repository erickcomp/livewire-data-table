<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProvidesDataTableData
{
    public function dataTableData(LwDataRetrievalParams $params): LengthAwarePaginator;
}
