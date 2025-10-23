<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class QueryBuilderDataSource implements DataSource
{
    public const PAGINATION_SIMPLE = 'simple';
    public const PAGINATION_LENGTH_AWARE = 'length_aware';
    public const PAGINATION_CURSOR = 'cursor';
    public const PAGINATION_DEFAULT = self::PAGINATION_LENGTH_AWARE;
    public function __construct(
        public QueryBuilder|EloquentBuilder $query,
        protected string $paginationType,
    ) {}

    /**
     * Returns paginated data using the provided query builder
     */
    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator
    {
        return match ($this->paginationType) {
            static::PAGINATION_LENGTH_AWARE => $this->getDataQuery($params)->paginate(perPage: $params->perPage, pageName: $params->pageName, page: $params->page),
            static::PAGINATION_CURSOR => $this->getDataQuery($params)->cursorPaginate(perPage: $params->perPage, cursorName: $params->pageName),
            static::PAGINATION_SIMPLE => $this->getDataQuery($params)->simplePaginate(perPage: $params->perPage, pageName: $params->pageName, page: $params->page),
        };
    }

    public function getDataQuery(LwDataRetrievalParams $params): QueryBuilder|EloquentBuilder
    {
        $query = clone $this->query;

        return $this->applyDataRetrievalParamsOnQuery($query, $params);
    }

    protected function applyDataRetrievalParamsOnQuery(QueryBuilder|EloquentBuilder $query, LwDataRetrievalParams $params): QueryBuilder|EloquentBuilder
    {
        $this->applyDataTableFiltersOnQuery($query, $params->filters);
        $this->applyDataTableColumnsSearchOnQuery($query, $params->columnsSearch);
        $this->applyDataTableSearchOnQuery($query, $params->search);
        $this->applyDataTableColumnsSortingOnQuery($query, $params->sortBy);
        $this->applyDataTableSortingDirectionOnQuery($query, $params->sortDir);

        return $query;
    }

    protected function applyDataTableFiltersOnQuery(QueryBuilder|EloquentBuilder $query, ?array $filters)
    {
        //
    }

    protected function applyDataTableColumnsSearchOnQuery(QueryBuilder|EloquentBuilder $query, ?array $columnsSearch)
    {
        if (empty($columnsSearch)) {
            return;
        }

        // if (\is_a($this->dataTable->dataSrc, SearchesDataTableColumns::class, true)) {
        //     $model = $this->dataTable->dataSrc;
        //     (new $model())->applyDataTableColumnsSearchToQuery($query, $columnsSearch);

        //     return;
        // }

        foreach ($columnsSearch as $dataField => $value) {
            $query->whereLike($dataField, "%$value%");
        }

    }

    protected function applyDataTableSearchOnQuery(QueryBuilder|EloquentBuilder $query, ?string $search)
    {
        if (empty($search)) {
            return;
        }

        $modelClass = $this->dataTable->dataSrc;
        if (\is_a($this->dataTable->dataSrc, SearchesDataTable::class, true)) {
            new $modelClass()->applyLwDataTableColumnsSearch($query, $search);
        } else {
            $model = new $modelClass();
            $columnsToSearch = collect(Schema::getColumns($model->getTable()))
                ->pluck('name')
                ->diff($model->getHidden());

            if ($columnsToSearch->isNotEmpty()) {
                $query->where(function ($orQuery) use ($columnsToSearch, $search) {
                    foreach ($columnsToSearch as $dataField) {
                        $orQuery->orWhereLike($dataField, "%$search%");
                    }
                });
            }
        }
    }

    protected function applyDataTableColumnsSortingOnQuery(QueryBuilder|EloquentBuilder $query, ?string $sortBy, string $sortDir = 'ASC')
    {
        if (empty(\trim($sortBy ?? ''))) {
            return;
        }

        $modelClass = $this->dataTable->dataSrc;
        if (\is_a($this->dataTable->dataSrc, SortsDataTable::class, true)) {
            new $modelClass()->applyLwDataTableSorting($query, $sortBy, $sortDir);
        } else {
            $sortDir = \in_array(\strtoupper($sortDir), ['ASC', 'DESC'])
                ? \strtoupper($sortDir)
                : 'ASC';

            $query->orderBy($sortBy, $sortDir);
        }
    }

    protected function applyDataTableSortingDirectionOnQuery(QueryBuilder|EloquentBuilder $query, ?string $sortDir)
    {
        if (empty($search)) {
            return;
        }

        $modelClass = $this->dataTable->dataSrc;
        if (\is_a($this->dataTable->dataSrc, SearchesDataTable::class, true)) {
            new $modelClass()->applyLwDataTableColumnsSearch($query, $search);
        } else {
            $model = new $modelClass();
            $columnsToSearch = collect(Schema::getColumns($model->getTable()))
                ->pluck('name')
                ->diff($model->getHidden());

            foreach ($columnsToSearch as $col) {
                $query->whereLike($col, "%$search%");
            }
        }
    }
}
