<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Laravie\SerializesQuery\Eloquent as EloquentBuilderSerializer;
use Illuminate\Support\Facades\Schema;

class EloquentBuilderDataSource implements DataSource
{
    public function __construct(
        protected EloquentBuilder $query,
        protected DataSourcePaginationType $paginationType,
    ) {}

    public function __serialize(): array
    {
        if (!\class_exists(EloquentBuilderSerializer::class)) {
            throw new \LogicException(
                'To use an Eloquent query builder as a data source, please install the package: laravie/serialize-queries',
            );
        }

        return [
            'query' => EloquentBuilderSerializer::serialize($this->query),
            'paginationType' => $this->paginationType,
        ];
    }

    public function __unserialize(array $data)
    {
        if (!\class_exists(EloquentBuilderSerializer::class)) {
            throw new \LogicException(
                'To use an Eloquent query builder as a data source, please install the package: laravie/serialize-queries',
            );
        }

        $this->query = EloquentBuilderSerializer::unserialize($data['query']);
        $this->paginationType = $data['paginationType'];
    }

    /**
     * Returns paginated data using the provided query builder
     */
    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection
    {
        return match ($this->paginationType) {
            DataSourcePaginationType::None => $this->getDataQuery($params)->get(),
            DataSourcePaginationType::LengthAware => $this->getDataQuery($params)->paginate(perPage: $params->perPage, pageName: $params->pageName, page: $params->page),
            DataSourcePaginationType::Cursor => $this->getDataQuery($params)->cursorPaginate(perPage: $params->perPage, cursorName: $params->pageName),
            DataSourcePaginationType::Simple => $this->getDataQuery($params)->simplePaginate(perPage: $params->perPage, pageName: $params->pageName, page: $params->page),
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

        $modelClass = $this->modelClass();
        if (\is_a($modelClass, SearchesDataTable::class, true)) {
            app()->make($modelClass)->applyDataTableSearchToQuery($query, $search);
        } else {
            $model = app()->make($modelClass);
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

        $modelClass = $this->modelClass();
        if (\is_a($modelClass, SortsDataTable::class, true)) {
            app()->make($modelClass)->applyLwDataTableSorting($query, $sortBy, $sortDir);
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

        $modelClass = $this->modelClass();
        if (\is_a($modelClass, SearchesDataTable::class, true)) {
            app()->make($modelClass)->applyLwDataTableColumnsSearch($query, $search);
        } else {
            $model = app()->make($modelClass);
            $columnsToSearch = collect(Schema::getColumns($model->getTable()))
                ->pluck('name')
                ->diff($model->getHidden());

            foreach ($columnsToSearch as $col) {
                $query->whereLike($col, "%$search%");
            }
        }
    }

    /**
     * @return class-string<EloquentModel>
     */
    protected function modelClass(): string
    {
        return $this->query->getModel();
    }
}
