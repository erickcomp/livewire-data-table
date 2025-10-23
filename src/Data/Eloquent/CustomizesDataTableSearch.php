<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface CustomizesDataTableSearch
{
    public function applyDataTableSearch(EloquentBuilder $query, LwDataRetrievalParams $params);
}
