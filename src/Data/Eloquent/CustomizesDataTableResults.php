<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;

interface CustomizesDataTableResults
{
    public function dataTableData(EloquentBuilder $query, LwDataRetrievalParams $params): iterable|Collection|Paginator|LengthAwarePaginator|CursorPaginator;
}
