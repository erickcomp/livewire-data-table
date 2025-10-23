<?php
namespace ErickComp\LivewireDataTable\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Collection;
class DataSourceFactory
{
    private function __construct()
    {
        //
    }
    public static function make(
        string|iterable|Collection|EloquentBuilder|QueryBuilder|Paginator|LengthAwarePaginator|CursorPaginator|null $source,
    ): DataSource {
        //
    }
}
