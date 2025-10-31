<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnEloquentBuilder;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Laravie\SerializesQuery\Eloquent as EloquentBuilderSerializer;

class EloquentBuilderDataSource implements DataSource
{
    use AppliesDataRetrievalParamsOnEloquentBuilder;
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

    public function getDataQuery(LwDataRetrievalParams $params): EloquentBuilder
    {
        $query = clone $this->query;

        return $this->applyDataRetrievalParamsOnQuery($query, $params);
    }

    /**
     * @return class-string<EloquentModel>
     */
    protected function modelClass(): string
    {
        return $this->query->getModel();
    }
}
