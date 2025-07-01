<?php

namespace ErickComp\LivewireDataTable\DataTable\Data;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface SortsDataTable
{
    public function applyLwDataTableSorting(EloquentBuilder $query, ?string $sortBy, ?string $sortDir);
}
