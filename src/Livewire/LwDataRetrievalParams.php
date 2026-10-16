<?php

namespace ErickComp\LivewireDataTable\Livewire;

use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnCollections;
use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnEloquentBuilder;
use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnQueryBuilder;
use ErickComp\LivewireDataTable\Concerns\PaginatesCollections;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Search;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class LwDataRetrievalParams
{
    use PaginatesCollections;

    protected bool $paginationDisabled = false;

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

    /**
     * A copy of these params whose pagination helpers (paginate(), simplePaginate(), cursorPaginate() and the
     * applyAnd*() ones) return every item in a single page. Used to reach all the rows matching the current search,
     * filters and sorting, e.g. to resolve a selection that spans several pages.
     */
    public function withoutPagination(): static
    {
        $params = clone $this;
        $params->paginationDisabled = true;

        return $params;
    }

    public function isPaginationDisabled(): bool
    {
        return $this->paginationDisabled;
    }

    public function chosenPaginationIsAll(): bool
    {
        return $this->perPage === $this->dataTable::PER_PAGE_ALL_OPTION_VALUE;
    }
    public function columnSearchMode(string $column): string
    {
        $col = $this->dataTable->columns->first(
            fn(Column $c) => $c->dataField === $column
        );

        return $col !== null ? $col->searchMode : Column::SEARCH_MODE_DEFAULT;
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

        if (!$data instanceof Collection && !$data instanceof LazyCollection) {
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
            if ($this->paginationDisabled) {
                $items = $data->get();

                return new LengthAwarePaginator($items, $items->count(), \max($items->count(), 1), 1, $this->paginatorOptions());
            }

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
            if ($this->paginationDisabled) {
                $items = $data->get();

                return new Paginator($items, \max($items->count(), 1), 1, $this->paginatorOptions());
            }

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
        if ($this->paginationDisabled) {
            $items = $data->get();

            return new CursorPaginator($items, \max($items->count(), 1), null, [
                'path' => Paginator::resolveCurrentPath(),
                'cursorName' => $this->pageName,
            ]);
        }

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

    protected function paginatorOptions(): array
    {
        return [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $this->pageName,
        ];
    }
}
