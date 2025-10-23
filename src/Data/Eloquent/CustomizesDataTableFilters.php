<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface CustomizesDataTableFilters
{
    public function applyDataTableFilters(EloquentBuilder $query, LwDataRetrievalParams $params);
}
