<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Collection;

interface ProvidesDataTableData
{
    public function dataTableData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection|iterable;
}
