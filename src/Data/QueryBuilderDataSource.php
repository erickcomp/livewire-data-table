<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Laravie\SerializesQuery\Query as QueryBuilderSerializer;

class QueryBuilderDataSource implements DataSource
{
    public function __construct(
        protected QueryBuilder $query,
        protected DataSourcePaginationType $paginationType,
    ) {}

    public function __serialize(): array
    {
        if (!\class_exists(QueryBuilderSerializer::class)) {
            throw new \LogicException(
                'To use a query builder instance as a data source, please install the package: laravie/serialize-queries',
            );
        }

        return [
            'query' => QueryBuilderSerializer::serialize($this->query),
            'paginationType' => $this->paginationType,
        ];
    }

    public function __unserialize(array $data)
    {
        if (!\class_exists(QueryBuilderSerializer::class)) {
            throw new \LogicException(
                'To use a query builder instance as a data source, please install the package: laravie/serialize-queries',
            );
        }

        $this->query = QueryBuilderSerializer::unserialize($data['query']);
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

    public function getDataQuery(LwDataRetrievalParams $params): QueryBuilder
    {
        $query = clone $this->query;

        return $this->applyDataRetrievalParamsOnQuery($query, $params);
    }

    protected function applyDataRetrievalParamsOnQuery(QueryBuilder $query, LwDataRetrievalParams $params): QueryBuilder
    {
        $this->applyDataTableFiltersOnQuery($query, $params);
        $this->applyDataTableColumnsSearchOnQuery($query, $params);
        $this->applyDataTableSearchOnQuery($query, $params);
        $this->applyDataTableColumnsSortingOnQuery($query, $params);

        return $query;
    }

    protected function applyDataTableFiltersOnQuery(QueryBuilder $query, LwDataRetrievalParams $params)
    {
        //
    }

    protected function applyDataTableColumnsSearchOnQuery(QueryBuilder $query, LwDataRetrievalParams $params)
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

    protected function applyDataTableSearchOnQuery(QueryBuilder $query, LwDataRetrievalParams $params)
    {
        if (empty($search)) {
            return;
        }

        // @TODO: Implement search by using columns from data table object
        return;

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

    protected function applyDataTableColumnsSortingOnQuery(QueryBuilder $query, LwDataRetrievalParams $params)
    {
        if (empty(\trim($sortBy ?? ''))) {
            return;
        }

        $sortDir = \in_array(\strtoupper($sortDir), ['ASC', 'DESC'])
            ? \strtoupper($sortDir)
            : 'ASC';

        $query->orderBy($sortBy, $sortDir);
    }
}
