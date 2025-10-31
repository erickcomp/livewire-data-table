<?php
namespace ErickComp\LivewireDataTable\Concerns;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
trait PaginatesCollections
{
    protected function paginate(Collection $data, LwDataRetrievalParams $params): LengthAwarePaginator
    {
        $paginator = new LengthAwarePaginator(
            $data->forPage($params->page, $params->perPage),
            $data->count(),
            $params->perPage,
            $params->page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $params->pageName,
            ],
        );

        return $paginator;
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
