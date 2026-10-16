<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnCollections;
use ErickComp\LivewireDataTable\Concerns\PaginatesCollections;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use ErickComp\LivewireDataTable\Support\ServerCallable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

class CallableDataSource implements DataSource, ProvidesUnpaginatedData
{
    /** @var string|callable */
    protected string|array|object $dataSource;
    protected string $callableType;

    public function __construct(
        string|callable $dataSource,
        protected DataSourcePaginationType $paginationType,
    ) {
        //@TODO: Check if there's a safe way to allow closures to be used without any caveats
        if ($dataSource instanceof \Closure) {
            throw new \ValueError('Cannot use a closure as the data source for the component');
        }

        $callable = ServerCallable::tryFrom($dataSource);

        if ($callable === null) {
            $debugValue = \is_string($dataSource)
                ? $dataSource
                : \var_export($dataSource, true);

            throw new \ValueError("Cannot create a callable data source from the give value [$debugValue]");
        }

        // The normalized callable and its type are what goes into the data table's cache
        $this->callableType = $callable->type();
        $this->dataSource = $callable->callable();
    }

    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection|LazyCollection
    {
        $data = $this->getDataFromCallable($params);

        return $data instanceof LazyCollection
            ? $data
            : $this->paginate($data, $params);
    }

    /**
     * Calls the callable with params whose pagination helpers return every row (see
     * LwDataRetrievalParams::withoutPagination()), so a callable that paginates through them, like
     * applyAndPaginate(), hands over all the matching rows. A callable that paginates on its own gets only one page:
     * it can check $params->isPaginationDisabled() to skip it.
     */
    public function getUnpaginatedData(LwDataRetrievalParams $params): EloquentBuilder|QueryBuilder|Collection|LazyCollection
    {
        $data = $this->getDataFromCallable($params->withoutPagination());

        if ($data instanceof Paginator || $data instanceof LengthAwarePaginator || $data instanceof CursorPaginator) {
            return collect($data->items());
        }

        return $data;
    }

    protected function paginate(
        Paginator|LengthAwarePaginator|CursorPaginator|QueryBuilder|EloquentBuilder|Collection $data,
        LwDataRetrievalParams $params,
    ): Paginator|LengthAwarePaginator|CursorPaginator|Collection|LazyCollection {
        if ($data instanceof Collection) {
            if ($this->paginationType === DataSourcePaginationType::Cursor) {
                Log::notice("erickcomp/livewire-data-table: Static data for data table is using \"cursor\" pagination type, which can't be done. Simple pagination will be used");
            }

            return match ($this->paginationType) {
                DataSourcePaginationType::None => $data,
                DataSourcePaginationType::LengthAware => $params->paginate($data),
                DataSourcePaginationType::Simple, DataSourcePaginationType::Cursor => $params->simplePaginate($data),
            };
        }

        if ($data instanceof QueryBuilder || $data instanceof EloquentBuilder) {
            return match ($this->paginationType) {
                DataSourcePaginationType::None => $data->get(),
                DataSourcePaginationType::LengthAware => $params->paginate($data),
                DataSourcePaginationType::Cursor => $params->cursorPaginate($data),
                DataSourcePaginationType::Simple => $params->simplePaginate($data),
            };
        }

        return $data;
    }

    protected function getDataFromCallable(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection|LazyCollection|QueryBuilder|EloquentBuilder
    {

        $data = (new ServerCallable($this->dataSource))->call([LwDataRetrievalParams::class => $params]);

        if (
            $data instanceof Paginator
            || $data instanceof LengthAwarePaginator
            || $data instanceof CursorPaginator
            || $data instanceof Collection
            || $data instanceof LazyCollection
            || $data instanceof QueryBuilder
            || $data instanceof EloquentBuilder
        ) {
            return $data;
        }

        if (!\is_iterable($data)) {
            $types = \implode(
                '|',
                [
                    Paginator::class,
                    LengthAwarePaginator::class,
                    CursorPaginator::class,
                    Collection::class,
                    'iterable',
                ],
            );

            throw new \UnexpectedValueException('Data source callable must return ' . $types . ', ' . get_debug_type($data) . ' returned');
        }

        return collect($data);
    }
}
