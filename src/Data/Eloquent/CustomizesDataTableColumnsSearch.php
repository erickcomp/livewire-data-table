<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface CustomizesDataTableColumnsSearch
{
    public function applyDataTableColumnsSearch(EloquentBuilder $query, LwDataRetrievalParams $params);
}
