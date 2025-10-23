<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class QueryBuilderDataSource implements DataSource
{
    public const PAGINATION_SIMPLE = 'simple';
    public const PAGINATION_LENGTH_AWARE = 'length_aware';
    public const PAGINATION_CURSOR = 'cursor';
    public const PAGINATION_DEFAULT = self::PAGINATION_LENGTH_AWARE;
    public function __construct(
        public QueryBuilder|EloquentBuilder $query,
    ) {}
    public function getData(LwDataRetrievalParams|null $params = null): array
    {
        return [];
    }

    /**
     * Returns paginated data using the provided query builder
     * 
     * @param class-string<Model> $class
     * @param \ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams $params
     * @return void
     */
    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator
    {
        return match ($this->paginationType) {
            static::PAGINATION_LENGTH_AWARE => $this->getQuery($params)->paginate(perPage: $params->perPage, pageName: $params->pageName, page: $params->page),
            static::PAGINATION_CURSOR => $this->getQuery($params)->cursorPaginate(perPage: $params->perPage, cursorName: $params->pageName),
            static::PAGINATION_SIMPLE => $this->getQuery($params)->simplePaginate(perPage: $params->perPage, pageName: $params->pageName, page: $params->page),
        };
    }

    /**
     * @param class-string<Model> $modelClass
     */
    protected function applyDataRetrievalParamsToQuery(EloquentBuilder $query, LwDataRetrievalParams $params): EloquentBuilder
    {
        $this->applyDataTableFiltersOnEloquentQuery($query, $params->filters);
        $this->applyDataTableColumnsSearchOnEloquentQuery($query, $params->columnsSearch);
        $this->applyDataTableSearchOnEloquentQuery($query, $params->search);
        $this->applyDataTableColumnsSortingOnEloquentQuery($query, $params->sortBy);
        $this->applyDataTableSortingDirectionOnEloquentQuery($query, $params->sortDir);

        return $query;
    }

    protected function applyDataTableFiltersOnEloquentQuery(EloquentBuilder $query, ?array $filters)
    {
        //
    }

    protected function applyDataTableColumnsSearchOnEloquentQuery(EloquentBuilder $query, ?array $columnsSearch)
    {
        if (empty($columnsSearch)) {
            return;
        }

        if (\is_a($this->dataTable->dataSrc, SearchesDataTableColumns::class, true)) {
            $model = $this->dataTable->dataSrc;
            (new $model())->applyDataTableColumnsSearchToQuery($query, $columnsSearch);

            return;
        }

        foreach ($columnsSearch as $dataField => $value) {
            $query->whereLike($dataField, "%$value%");
        }

    }

    protected function applyDataTableSearchOnEloquentQuery(EloquentBuilder $query, ?string $search)
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

    protected function applyDataTableColumnsSortingOnEloquentQuery(EloquentBuilder $query, ?string $sortBy, string $sortDir = 'ASC')
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

    protected function applyDataTableSortingDirectionOnEloquentQuery(EloquentBuilder $query, ?string $sortDir)
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
