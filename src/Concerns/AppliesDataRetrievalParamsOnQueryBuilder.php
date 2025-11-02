<?php

namespace ErickComp\LivewireDataTable\Concerns;

use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Query\Builder as QueryBuilder;
use ErickComp\LivewireDataTable\DataTable\Filter;

trait AppliesDataRetrievalParamsOnQueryBuilder
{
    protected function applyDataRetrievalParamsOnQueryBuilder(QueryBuilder $query, LwDataRetrievalParams $params): QueryBuilder
    {
        $this->applyDataTableFiltersOnQueryBuilder($query, $params);
        $this->applyDataTableColumnsSearchOnQueryBuilder($query, $params);
        $this->applyDataTableSearchOnQueryBuilder($query, $params);
        $this->applyDataTableColumnsSortingOnQueryBuilder($query, $params);

        return $query;
    }

    protected function applyDataTableFiltersOnQueryBuilder(QueryBuilder $query, LwDataRetrievalParams $params)
    {
        if (empty($params->filters)) {
            return;
        }

        if (!empty($params->filters)) {
            foreach ($params->filters as $filter) {

                switch ($filter['mode']) {
                    case Filter::MODE_EXACT:
                        $value = $filter['value'];
                        $query->where($filter['column'], $value);

                        break;

                    case Filter::MODE_CONTAINS:
                        $value = $filter['value'];
                        $query->whereLike($filter['column'], "%$value%");

                        break;

                    case Filter::MODE_STARTS_WITH:
                        $value = $filter['value'];
                        $query->whereLike($filter['column'], "$value%");

                        break;

                    case Filter::MODE_ENDS_WITH:
                        $value = $filter['value'];
                        $query->whereLike($filter['column'], "%$value");

                        break;

                    case Filter::MODE_FULLTEXT:
                        $value = $filter['value'];
                        $query->whereFullText($filter['column'], $value);

                        break;

                    case Filter::MODE_IN:
                        $vals = [];
                        foreach ($filter['value'] as $v) {
                            $vals[] = $v;
                        }
                        $query->whereIn($filter['column'], $vals);

                        break;

                    case Filter::MODE_RANGE:
                        $query
                            ->when($filter['value']['from'] ?? false, function ($query) use ($filter) {
                                $value = $filter['value']['from'];
                                $query->where($filter['column'], '>=', $value);
                            })
                            ->when($filter['value']['to'] ?? false, function ($query) use ($filter) {
                                $value = $filter['value']['to'];
                                $query->where($filter['column'], '<=', $value);
                            });
                        break;
                }
            }
        }
    }

    protected function applyDataTableColumnsSearchOnQueryBuilder(QueryBuilder $query, LwDataRetrievalParams $params)
    {
        if (empty($params->columnsSearch)) {
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

    protected function applyDataTableSearchOnQueryBuilder(QueryBuilder $query, LwDataRetrievalParams $params)
    {
        if (empty($params->search)) {
            return;
        }

        $columnsToSearch = $params->dataTableSearchDataFields();

        if ($columnsToSearch === true) {
            $errmsg = 'When using the x-data-table.search component with an x-data-table backed by [' . static::class . '] data source, you must provide the data-fields that the search should run on';

            throw new \LogicException($errmsg);
        }

        if (!empty($columnsToSearch)) {
            $query->where(function (QueryBuilder $orQuery) use ($columnsToSearch, $params) {
                $search = \trim($params->search);
                $fullTextDataFields = [];

                foreach ($columnsToSearch as $dataField => $mode) {
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

    protected function applyDataTableColumnsSortingOnQueryBuilder(QueryBuilder $query, LwDataRetrievalParams $params)
    {
        if (empty(\trim($params->sortBy ?? ''))) {
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
