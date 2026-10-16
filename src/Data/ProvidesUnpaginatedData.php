<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * A data source that can hand over every row matching the search, filters and sorting in the given params, across
 * all pages: a query (not run yet) when it is backed by one, or the rows themselves otherwise.
 */
interface ProvidesUnpaginatedData
{
    public function getUnpaginatedData(LwDataRetrievalParams $params): EloquentBuilder|QueryBuilder|Collection|LazyCollection;
}
