<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface CustomizesDataTableQuery
{
    public function dataTableQuery(): EloquentBuilder;
}
