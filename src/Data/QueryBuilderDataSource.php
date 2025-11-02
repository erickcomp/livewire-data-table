<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnQueryBuilder;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Laravie\SerializesQuery\Query as QueryBuilderSerializer;

class QueryBuilderDataSource implements DataSource
{
    use AppliesDataRetrievalParamsOnQueryBuilder;

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

        return $this->applyDataRetrievalParamsOnQueryBuilder($query, $params);
    }
}
