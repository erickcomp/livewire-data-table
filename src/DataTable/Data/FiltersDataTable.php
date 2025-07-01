<?php

namespace ErickComp\LivewireDataTable\DataTable\Data;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface FiltersDataTable
{
    public function applyLwDataTableFilters(EloquentBuilder $query, ?string $search);
}
