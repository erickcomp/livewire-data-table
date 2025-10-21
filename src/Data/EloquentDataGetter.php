<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class EloquentDataGetter
{
    public function __construct(
        protected string $modelClass,
    ) {}

    public function makeModel(): Model
    {
        return app()->make($this->modelClass);
    }

    public function getQuery(LwDataRetrievalParams $params): EloquentBuilder
    {
        return $this->applyDataRetrievalParamsToQuery($this->makeModel()->newQuery(), $params);
    }

    /**
     * Returns paginated data using the provided eloquent model class
     * 
     * @param class-string<Model> $class
     * @param \ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams $params
     * @return void
     */
    public function getData(string $modelClass, LwDataRetrievalParams $params): LengthAwarePaginator
    {
        if (\is_a($this->modelClass, ProvidesDataTableData::class, true)) {
            return $this->makeModel()->dataTableData($params);
        }

        return $this->applyDataRetrievalParamsToQuery($this->getQuery($params))->paginate();
    }

    protected function providesDataTableData(string $modelClass): bool
    {
        if (\is_a($modelClass, ProvidesDataTableData::class, true)) {
            return true;
        }

        if (!\method_exists($modelClass, 'dataTableData')) {
            return false;
        }

        $reflMethod = new \ReflectionMethod("$modelClass::dataTableData");

    }

    /**
     * @param class-string<Model> $modelClass
     */
    protected function applyDataRetrievalParamsToQuery(EloquentBuilder $query, LwDataRetrievalParams $params): EloquentBuilder
    {
        $this->applyDataTableFiltersOnEloquentQuery($query, $params->filters);
        $this->applyDataTableColumnsSearchOnEloquentQuery($query, $params->columnsSearch);
        $this->applyDataTableSearchOnEloquentQuery($query, $params->search);
        $this->applyDataTableColumnsSortingOnEloquentQuery($query, $params->sortBy);
        $this->applyDataTableSortingDirectionOnEloquentQuery($query, $params->sortDir);

        return $query;
    }

    protected function applyDataTableFiltersOnEloquentQuery(EloquentBuilder $query, ?array $filters)
    {
        //
        dd($filters);
    }

    protected function applyDataTableColumnsSearchOnEloquentQuery(EloquentBuilder $query, ?array $columnsSearch)
    {
        if (empty($columnsSearch)) {
            return;
        }

        if (\is_a($this->dataTable->dataSrc, SearchesDataTableColumns::class, true)) {
            $model = $this->dataTable->dataSrc;
            new $model()->applyLwDataTableColumnsSearch($query, $columnsSearch);
        } else {
            foreach ($columnsSearch as $dataField => $value) {
                $query->whereLike($dataField, "%$value%");
            }
        }
    }

    protected function applyDataTableSearchOnEloquentQuery(EloquentBuilder $query, ?string $search)
    {
        if (empty($search)) {
            return;
        }

        $modelClass = $this->dataTable->dataSrc;
        if (\is_a($this->dataTable->dataSrc, SearchesDataTable::class, true)) {
            new $modelClass()->applyLwDataTableColumnsSearch($query, $search);
        } else {
            $model = new $modelClass();
            $columnsToSearch = collect(Schema::getColumns($model->getTable()))
                ->pluck('name')
                ->diff($model->getHidden());

            if ($columnsToSearch->isNotEmpty()) {
                $query->where(function ($orQuery) use ($columnsToSearch, $search) {
                    foreach ($columnsToSearch as $dataField) {
                        $orQuery->orWhereLike($dataField, "%$search%");
                    }
                });
            }
        }
    }

    protected function applyDataTableColumnsSortingOnEloquentQuery(EloquentBuilder $query, ?string $sortBy, string $sortDir = 'ASC')
    {
        if (empty(\trim($sortBy ?? ''))) {
            return;
        }

        $modelClass = $this->dataTable->dataSrc;
        if (\is_a($this->dataTable->dataSrc, SortsDataTable::class, true)) {
            new $modelClass()->applyLwDataTableSorting($query, $sortBy, $sortDir);
        } else {
            $sortDir = \in_array(\strtoupper($sortDir), ['ASC', 'DESC'])
                ? \strtoupper($sortDir)
                : 'ASC';

            $query->orderBy($sortBy, $sortDir);
        }
    }

    protected function applyDataTableSortingDirectionOnEloquentQuery(EloquentBuilder $query, ?string $sortDir)
    {
        if (empty($search)) {
            return;
        }

        $modelClass = $this->dataTable->dataSrc;
        if (\is_a($this->dataTable->dataSrc, SearchesDataTable::class, true)) {
            new $modelClass()->applyLwDataTableColumnsSearch($query, $search);
        } else {
            $model = new $modelClass();
            $columnsToSearch = collect(Schema::getColumns($model->getTable()))
                ->pluck('name')
                ->diff($model->getHidden());

            foreach ($columnsToSearch as $col) {
                $query->whereLike($col, "%$search%");
            }
        }
    }
}
