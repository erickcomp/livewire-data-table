<?php

namespace ErickComp\LivewireDataTable\Livewire;

use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnCollections;
use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnEloquentBuilder;
use ErickComp\LivewireDataTable\Concerns\PaginatesCollections;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Search;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder as QueryBuilder;
use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnQueryBuilder;

class LwDataRetrievalParams
{
    use PaginatesCollections;
    /**
     * @param string[] $searchDataFields
     */
    public function __construct(
        public ?int $page,
        public ?string $perPage,
        public string $pageName,
        public ?string $search,
        //public array|true $searchDataFields,
        public ?array $columnsSearch,
        public ?array $filters,
        public ?string $sortBy,
        public ?string $sortDir,
        public int $collectionsSortingFlags,
        protected DataTable $dataTable,
    ) {}

    public function chosenPaginationIsAll(): bool
    {
        return $this->perPage === $this->dataTable::PER_PAGE_ALL_OPTION_VALUE;
    }
    public function columnSearchMode(string $column): string
    {
        return Column::SEARCH_MODE_DEFAULT;
    }

    public function dataTableSearchDataFields(): bool|array
    {
        return $this->dataTable->search->dataFields;
    }

    public function searchModeForDataField(string $dataField): string
    {
        return Search::SEARCH_MODE_DEFAULT;
    }

    public function filterMode(string $filter)
    {
        return Filter::MODE_CONTAINS;
    }

    public function apply(QueryBuilder|EloquentBuilder|Collection|iterable $data)
    {
        if ($data instanceof EloquentBuilder) {
            $dataRetriever = new class ($data->getModel()::class) {
                use AppliesDataRetrievalParamsOnEloquentBuilder {
                    applyDataRetrievalParamsOnEloquentBuilder as public;
                }

                public function __construct(protected string $modelClass) {}

                protected function modelClass(): string
                {
                    return $this->modelClass;
                }
            };

            return $dataRetriever->applyDataRetrievalParamsOnEloquentBuilder($data, $this);
        }

        if ($data instanceof QueryBuilder) {
            $dataRetriever = new class () {
                use AppliesDataRetrievalParamsOnQueryBuilder {
                    applyDataRetrievalParamsOnQueryBuilder as public;
                }
            };

            return $dataRetriever->applyDataRetrievalParamsOnQueryBuilder($data, $this);
        }

        if (!$data instanceof Collection) {
            $data = collect($data);
        }

        $dataRetriever = new class () {
            use AppliesDataRetrievalParamsOnCollections { applyDataRetrievalParamsOnCollection as public;
            }
        };

        return $dataRetriever->applyDataRetrievalParamsOnCollection($data, $this);
    }

    public function paginate(Collection|QueryBuilder|EloquentBuilder $data): LengthAwarePaginator
    {
        if ($data instanceof EloquentBuilder || $data instanceof QueryBuilder) {
            return $data->paginate(perPage: $this->perPage, pageName: $this->pageName, page: $this->page);
        }

        $paginator = new class {
            use PaginatesCollections { paginate as public;
            }
        };

        return $paginator->paginate($data, $this);
    }

    public function simplePaginate(Collection|QueryBuilder|EloquentBuilder $data): Paginator
    {
        if ($data instanceof EloquentBuilder || $data instanceof QueryBuilder) {
            return $data->simplePaginate(perPage: $this->perPage, pageName: $this->pageName, page: $this->page);
        }

        $paginator = new class {
            use PaginatesCollections { simplePaginate as public;
            }
        };

        return $paginator->simplePaginate($data, $this);
    }

    public function cursorPaginate(QueryBuilder|EloquentBuilder $data): CursorPaginator
    {
        return $data->cursorPaginate(perPage: $this->perPage, cursorName: $this->pageName);
    }

    public function applyAndPaginate(QueryBuilder|EloquentBuilder|Collection|iterable $data): LengthAwarePaginator
    {
        $data = $this->apply($data);
        return $this->paginate($data);
    }

    public function applyAndSimplePaginate(QueryBuilder|EloquentBuilder|Collection|iterable $data): Paginator
    {
        $data = $this->apply($data);
        return $this->simplePaginate($data);
    }

    public function applyAndCursorPaginate(QueryBuilder|EloquentBuilder $data): CursorPaginator
    {
        $data = $this->apply($data);
        return $this->cursorPaginate($data);
    }
}
