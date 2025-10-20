<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Builder as DatabaseQueryBuilder;

interface BuildsDataTableQuery
{
    public function buildLwDataTableQuery(LwDataRetrievalParams $params): DatabaseQueryBuilder;
}
