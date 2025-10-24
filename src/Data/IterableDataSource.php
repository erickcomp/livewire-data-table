<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class IterableDataSource implements DataSource
{
    protected string $sessionDataKey;
    public function __construct(
        iterable $data,
        protected string $paginationType,
        string $componentName,
        string $htmlNameAttribute,
        string $currentUrl,
    ) {
        if (!$data instanceof Collection) {
            if (!\is_array($data)) {
                $data = \iterator_to_array($data);
            }

            $data = collect($data);
        }
        $this->sessionDataKey = "x-{$componentName}-{$htmlNameAttribute}@{$currentUrl}";
        Session::put($this->sessionDataKey, $data);
    }
    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection
    {
        $data = Session::get($this->sessionDataKey);

        if ($data === null) {
            // @TODO: Throw some sort of data expired so LwData Table triggers refresh of the component
            Log::warning("erickcomp/livewire-data-table: Could not data source [{$this->sessionDataKey}] in session");
            $data = collect();
        }

        $data = $this->applyDataRetrievalParamsOnCollection($data, $params);

        // Cursor pagination does not make sense for static data, but we simple pagination for it
        if ($this->paginationType === static::PAGINATION_CURSOR) {
            Log::notice("erickcomp/livewire-data-table: Static data for data table [[{$this->sessionDataKey}]] is using \"cursor\" pagination type, which can't be done. Simple pagination will be used");
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
