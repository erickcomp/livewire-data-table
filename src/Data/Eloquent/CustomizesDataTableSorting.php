<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface CustomizesDataTableSorting
{
    public function applyDataTableSorting(EloquentBuilder $query, LwDataRetrievalParams $params);
}
