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
        if ($params->chosenPaginationIsAll()) {
            $perPage = $data->count();
            $page = 1;
        } else {
            $perPage = $params->perPage;
            $page = $params->page;
        }

        $paginator = new LengthAwarePaginator(
            $data->forPage($page, $perPage),
            $data->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $params->pageName,
            ],
        );

        return $paginator;
    }

    protected function simplePaginate(Collection $data, LwDataRetrievalParams $params): Paginator
    {
        if ($params->chosenPaginationIsAll()) {
            $perPage = $data->count();
            $page = 1;
        } else {
            $perPage = $params->perPage;
            $page = $params->page;
        }

        $paginator = new Paginator(
            $data->forPage($page, $perPage),

            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $params->pageName,
            ],
        );

        return $paginator->hasMorePagesWhen($data->count() > ($perPage * $page));
    }
}
