<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Wireable;

class IterableDataSource implements StaticDataDataSource, Wireable
{
    use PaginatesCollections;

    protected Collection $data;
    protected DataSourcePaginationType $paginationType;

    public function __construct(
        iterable $data,
        DataSourcePaginationType $paginationType,
    ) {
        if (!$data instanceof Collection) {
            $data = collect($data);
        }

        $this->data = $data;
        $this->paginationType = $paginationType;
    }

    public static function fromLivewire($value): static
    {
        return \decrypt($value['data']);
    }

    public function toLivewire(): array
    {
        return ['data' => \encrypt($this)];
    }

    public function getStaticData(): Collection
    {
        return $this->data;
    }

    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection
    {
        $data = $this->applyDataRetrievalParamsOnCollection($this->data, $params);

        // Cursor pagination does not make sense for static data, but we simple pagination for it
        if ($this->paginationType === DataSourcePaginationType::Cursor) {
            Log::notice("erickcomp/livewire-data-table: Static data for data table is using \"cursor\" pagination type, which can't be done. Simple pagination will be used");
        }

        return match ($this->paginationType) {
            DataSourcePaginationType::None => $data,
            DataSourcePaginationType::LengthAware => $this->paginate($data, $params),
            DataSourcePaginationType::Cursor, DataSourcePaginationType::Simple => $this->simplePaginate($data, $params),
        };
    }

    protected function applyDataRetrievalParamsOnCollection(Collection $data, LwDataRetrievalParams $params): Collection
    {
        // @TODO: Create filters, search and whatnot on Collection
        return $data;
    }
}
