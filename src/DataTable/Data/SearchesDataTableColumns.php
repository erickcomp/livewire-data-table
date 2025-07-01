<?php

namespace ErickComp\LivewireDataTable\DataTable\Data;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface SearchesDataTableColumns
{
    public function applyLwDataTableColumnsSearch(EloquentBuilder $query, ?array $columnsSearch);
}
