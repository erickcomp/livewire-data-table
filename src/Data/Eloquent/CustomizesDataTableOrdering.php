<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface CustomizesDataTableOrdering
{
    public function applyDataTableOrdering(EloquentBuilder $query, LwDataRetrievalParams $params);
}
