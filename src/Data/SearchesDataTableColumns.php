<?php

namespace ErickComp\LivewireDataTable\Data;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface SearchesDataTableColumns
{
    public function applyDataTableColumnsSearchToQuery(EloquentBuilder $query, array $columnsSearch);
}
