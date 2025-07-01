<?php

namespace ErickComp\LivewireDataTable\DataTable\Data;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface SearchesDataTable
{
    public function applyLwDataTableSearch(EloquentBuilder $query, ?string $search);
}
