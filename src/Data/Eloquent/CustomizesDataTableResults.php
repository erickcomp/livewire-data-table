<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

interface CustomizesDataTableResults
{
    public function dataTableData(EloquentBuilder $query, LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection;
}
