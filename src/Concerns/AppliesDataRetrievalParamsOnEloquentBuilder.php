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

trait AppliesDataRetrievalParamsOnEloquentBuilder
{
    /**
     * Returns the Eloquent Model class that's related to this data source
     * 
     * @return class-string<EloquentModel>
     */
    abstract protected function modelClass(): string;

    protected function modelInstance(): EloquentModel
    {
        static $model = null;

        if ($model === null) {
            $model = app()->make($this->modelClass());
        }

        return $model;
    }

    protected function applyDataRetrievalParamsOnQuery(EloquentBuilder $query, LwDataRetrievalParams $params): EloquentBuilder
    {
        $this->applyDataTableFiltersOnQuery($query, $params);
        $this->applyDataTableColumnsSearchOnQuery($query, $params);
        $this->applyDataTableSearchOnQuery($query, $params);
        $this->applyDataTableColumnsSortingOnQuery($query, $params);

        return $query;
    }
    protected function applyDataTableFiltersOnQuery(EloquentBuilder $query, LwDataRetrievalParams $params)
    {
        if (empty($params->filters)) {
            return;
        }

        if (!empty($params->filters)) {
            foreach ($params->filters as $filter) {

                switch ($filter['mode']) {
                    case Filter::MODE_EXACT:
                        $value = EloquentCaster::castValueFromFilter($query, $filter);
                        $query->where($filter['column'], $value);

                        break;

                    case Filter::MODE_CONTAINS:
                        $value = EloquentCaster::castValueFromFilter($query, $filter);
                        $query->whereLike($filter['column'], "%$value%");

                        break;

                    case Filter::MODE_STARTS_WITH:
                        $value = EloquentCaster::castValueFromFilter($query, $filter);
                        $query->whereLike($filter['column'], "$value%");

                        break;

                    case Filter::MODE_ENDS_WITH:
                        $value = EloquentCaster::castValueFromFilter($query, $filter);
                        $query->whereLike($filter['column'], "%$value");

                        break;

                    case Filter::MODE_FULLTEXT:
                        $value = EloquentCaster::castValueFromFilter($query, $filter);
                        $query->whereFullText($filter['column'], $value);

                        break;

                    case Filter::MODE_IN:
                        $castedValues = [];
                        foreach ($filter['value'] as $v) {
                            $castedValues = EloquentCaster::castValueFromFilter($query, $filter);
                        }
                        $query->whereIn($filter['column'], $castedValues);

                        break;

                    case Filter::MODE_RANGE:
                        $query
                            ->when($filter['value']['from'] ?? false, function ($query) use ($filter) {
                                $value = EloquentCaster::castValueFromFilter($query, $filter, 'from');
                                $query->where($filter['column'], '>=', $value);
                            })
                            ->when($filter['value']['to'] ?? false, function ($query) use ($filter) {
                                $value = EloquentCaster::castValueFromFilter($query, $filter, 'to');
                                $query->where($filter['column'], '<=', $value);
                            });
                        break;
                }
            }
        }
    }

    protected function applyDataTableColumnsSearchOnQuery(EloquentBuilder $query, LwDataRetrievalParams $params)
    {
        if (empty($params->columnsSearch)) {
            return;
        }

        if (\is_a($this->modelClass(), CustomizesDataTableColumnsSearch::class, true)) {
            $this->modelInstance()->applyDataTableColumnsSearch($query, $params);

            return;
        }

        foreach ($params->columnsSearch as $dataField => $value) {
            switch ($params->columnSearchMode($dataField)) {
                case Column::SEARCH_MODE_CONTAINS:
                    $query->whereLike($dataField, "%$value%");
                    break;

                case Column::SEARCH_MODE_STARTS_WITH:
                    $query->whereLike($dataField, "$value%");
                    break;

                case Column::SEARCH_MODE_ENDS_WITH:
                    $query->whereLike($dataField, "%$value");
                    break;

                case Column::SEARCH_MODE_EXACT:
                    $query->where($dataField, $value);
                    break;

                case Column::SEARCH_MODE_FULLTEXT:
                    $query->whereFullText($dataField, $value);
                    break;
            }
        }
    }

    protected function applyDataTableSearchOnQuery(EloquentBuilder $query, LwDataRetrievalParams $params)
    {
        if (empty($params->search)) {
            return;
        }

        if (\is_a($this->modelClass(), CustomizesDataTableColumnsSearch::class, true)) {
            $this->modelInstance()->applyDataTableSearchToQuery($query, $params);

            return;
        }

        $columnsToSearch = $params->dataTableSearchDataFields();

        if ($columnsToSearch === true) {
            $columnsToSearchDataFields = collect(Schema::getColumns($this->modelInstance()->getTable()))
                ->pluck('name')
                ->diff($this->modelInstance()->getHidden());

            $columnsToSearch = \array_fill_keys($columnsToSearchDataFields->all(), Search::SEARCH_MODE_DEFAULT);
        }

        if (!empty($columnsToSearch)) {
            $query->where(function (EloquentBuilder $orQuery) use ($columnsToSearch, $params) {
                $search = \trim($params->search);
                $fullTextDataFields = [];

                foreach ($columnsToSearch as $dataField => $mode) {
                    //$orQuery->orWhereLike($dataField, "%$search%");
                    switch ($mode) {
                        case Search::SEARCH_MODE_CONTAINS:
                            $orQuery->orWhereLike($dataField, "%$search%");
                            break;

                        case Search::SEARCH_MODE_STARTS_WITH:
                            $orQuery->orWhereLike($dataField, "$search%");
                            break;

                        case Search::SEARCH_MODE_ENDS_WITH:
                            $orQuery->orWhereLike($dataField, "%$search");
                            break;

                        case Search::SEARCH_MODE_EXACT:
                            $orQuery->orWhere($dataField, $search);
                            break;

                        case Search::SEARCH_MODE_FULLTEXT:
                            //$orQuery->whereFullText($dataField, $search);
                            $fullTextDataFields[] = $dataField;
                            break;
                    }
                }

                if (!empty($fullTextDataFields)) {
                    $orQuery->orWhereFullText($fullTextDataFields, $search);
                }
            });
        }
    }

    protected function applyDataTableColumnsSortingOnQuery(EloquentBuilder $query, LwDataRetrievalParams $params)
    {
        if (empty(\trim($params->sortBy ?? ''))) {
            return;
        }

        $modelClass = $this->modelClass();
        if (\is_a($modelClass, CustomizesDataTableSorting::class, true)) {
            $this->modelInstance()->applyDataTableSorting($query, $params);

            return;
        }

        $sortBy = \trim($params->sortBy);

        $sortDir = empty(\trim($params->sortDir ?? ''))
            ? 'ASC'
            : \trim($params->sortDir);

        $sortDir = \in_array(\strtoupper($sortDir), ['ASC', 'DESC'])
            ? \strtoupper($sortDir)
            : 'ASC';

        $query->orderBy($sortBy, $sortDir);
    }
}
