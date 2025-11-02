<?php

namespace ErickComp\LivewireDataTable\Concerns;

use ErickComp\LivewireDataTable\Data\Eloquent\CustomizesDataTableColumnsSearch;
use ErickComp\LivewireDataTable\Data\Eloquent\CustomizesDataTableSorting;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Schema;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\Data\Eloquent\EloquentCaster;
use Illuminate\Support\Collection;

trait AppliesDataRetrievalParamsOnCollections
{
    protected function applyDataRetrievalParamsOnCollection(Collection $data, LwDataRetrievalParams $params): Collection
    {
        $data = $this->applyDataTableFiltersOnCollection($data, $params);
        $data = $this->applyDataTableColumnsSearchOnCollection($data, $params);

        return $data;
    }

    protected function applyDataTableFiltersOnCollection(Collection $collection, LwDataRetrievalParams $params): Collection
    {
        if (empty($params->filters)) {
            return clone $collection;
        }

        $clauses = [];

        foreach ($params->filters as $filter) {
            switch ($filter['mode']) {
                case Filter::MODE_EXACT:
                    $clauses[] = fn($item) => \data_get($item, $filter['column']) == $filter['value']; // not using strict because all filter values are strings

                    break;

                case Filter::MODE_CONTAINS:
                    $clauses[] = fn($item) => \str_contains(\strtolower(\data_get($item, $filter['column'])), \strtolower($filter['value']));

                    break;

                case Filter::MODE_STARTS_WITH:
                    $clauses[] = fn($item) => \str_starts_with(\data_get($item, $filter['column']), $filter['value']);

                    break;

                case Filter::MODE_ENDS_WITH:
                    $clauses[] = fn($item) => \str_ends_with(\data_get($item, $filter['column']), $filter['value']);

                    break;

                case Filter::MODE_FULLTEXT:
                    throw new \ValueError("Iterable ({$this->originalType}) data sources do not support full text filters");

                case Filter::MODE_IN:
                    $vals = [];
                    foreach ($filter['value'] as $v) {
                        $vals[] = $v;
                    }
                    $clauses[] = fn($item) => \in_array(\data_get($item, $filter['column']), $vals);

                    break;

                case Filter::MODE_RANGE:
                    $clauses[] = function ($item) use ($filter) {
                        $itemValueIsGreatherThanOrEqualToFilterValue = isset($filter['value']['from'])
                            ? \data_get($item, $filter['column']) >= $filter['value']
                            : true;

                        $itemValueIsLesserThanOrEqualToFilterValue = isset($filter['value']['to'])
                            ? \data_get($item, $filter['column']) <= $filter['value']
                            : true;

                        return $itemValueIsGreatherThanOrEqualToFilterValue && $itemValueIsLesserThanOrEqualToFilterValue;

                    };
            }
        }

        return $collection->filter(function ($item) use ($clauses) {
            foreach ($clauses as $clause) {
                if (!$clause($item)) {
                    return false;
                }
            }

            return true;
        });
    }

    protected function applyDataTableColumnsSearchOnCollection(Collection $collection, LwDataRetrievalParams $params): Collection
    {
        return $collection;
    }

    protected function applyDataTableSearchOnCollection(Collection $collection, LwDataRetrievalParams $params): Collection
    {
        return $collection;
    }


    protected function applyDataTableColumnsSortingOnCollection(Collection $collection, LwDataRetrievalParams $params): Collection
    {
        return $collection;
    }
}
