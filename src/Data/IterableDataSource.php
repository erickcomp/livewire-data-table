<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnCollections;
use ErickComp\LivewireDataTable\Concerns\PaginatesCollections;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\EnumeratesValues;
use Livewire\Wireable;
use ErickComp\LivewireDataTable\DataTable\Filter;

class IterableDataSource implements StaticDataDataSource, Wireable
{
    use AppliesDataRetrievalParamsOnCollections;
    use PaginatesCollections;

    protected string $originalType;
    protected Collection $data;
    protected DataSourcePaginationType $paginationType;

    public function __construct(
        iterable $data,
        DataSourcePaginationType $paginationType,
    ) {
        $this->originalType = \get_debug_type($data);

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

    
}
