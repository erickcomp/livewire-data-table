<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Wireable;

class IterableDataSource implements StaticDataDataSource, Wireable
{
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
        return decrypt($value);
    }

    public function toLivewire(): string
    {
        return encrypt($this);
    }

    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection
    {
        $data = $this->applyDataRetrievalParamsOnCollection($this->data, $params);

        // Cursor pagination does not make sense for static data, but we simple pagination for it
        if ($this->paginationType === static::PAGINATION_CURSOR) {
            Log::notice("erickcomp/livewire-data-table: Static data for data table [[{$this->sessionKey}]] is using \"cursor\" pagination type, which can't be done. Simple pagination will be used");
        }

        return match ($this->paginationType) {
            static::PAGINATION_NONE => $data,
            static::PAGINATION_LENGTH_AWARE => $this->paginate($data, $params),
            static::PAGINATION_CURSOR, static::PAGINATION_SIMPLE => $this->simplePaginate($data, $params),
        };
    }

    protected function applyDataRetrievalParamsOnCollection(Collection $data, LwDataRetrievalParams $params): Collection
    {
        // @TODO: Create filters, search and whatnot on Collection
        return $data;
    }

    protected function paginate(Collection $data, LwDataRetrievalParams $params): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $data->forPage($params->perPage, $params->page),
            $data->count(),
            $params->perPage,
            $params->page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $params->pageName,
            ],
        );
    }

    protected function simplePaginate(Collection $data, LwDataRetrievalParams $params): Paginator
    {
        return new Paginator(
            $data->forPage($params->perPage, $params->page),
            $params->perPage,
            $params->page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $params->pageName,
            ],
        );
    }
}
