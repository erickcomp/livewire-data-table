<?php

namespace ErickComp\LivewireDataTable\Data;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface SearchesDataTable
{
    public function applyDataTableSearchToQuery(EloquentBuilder $query, array $columnsSearch);
}
