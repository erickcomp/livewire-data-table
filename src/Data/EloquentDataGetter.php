<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class EloquentDataGetter
{
    public const PAGINATION_SIMPLE = 'simple';
    public const PAGINATION_LENGTH_AWARE = 'length_aware';
    public const PAGINATION_CURSOR = 'cursor';
    public const PAGINATION_DEFAULT = self::PAGINATION_LENGTH_AWARE;

    private const ITERABLE_PSEUDOTYPE = 'iterable';

    public function __construct(
        protected DataTable $dataTable,
    ) {
        if (!\is_a($dataTable->dataSrc, Model::class, true)) {
            throw new \LogicException("The value [{$dataTable->dataSrc}] is not an Eloquent Model class");
        }
    }

    public function getQuery(LwDataRetrievalParams $params): EloquentBuilder
    {
        return $this->applyDataRetrievalParamsToQuery($this->newQuery(), $params);
    }

    /**
     * Returns paginated data using the provided eloquent model class
     * 
     * @param class-string<Model> $class
     * @param \ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams $params
     * @return void
     */
    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection|iterable
    {
        if ($this->modelProvidesDataTableData()) {
            return $this->makeModel()->dataTableData($params);
        }

        return match ($this->dataTable->dataSrcPagination) {
            static::PAGINATION_LENGTH_AWARE => $this->getQuery($params)->paginate(perPage: $params->perPage, pageName: $params->pageName, page: $params->page),
            static::PAGINATION_CURSOR => $this->getQuery($params)->cursorPaginate(perPage: $params->perPage, cursorName: $params->pageName),
            static::PAGINATION_SIMPLE => $this->getQuery($params)->simplePaginate(perPage: $params->perPage, pageName: $params->pageName, page: $params->page),
        };
    }

    protected function makeModel(): Model
    {
        return app()->make($this->dataTable->dataSrc);
    }

    protected function newQuery(): EloquentBuilder
    {
        return $this->makeModel()->newQuery();
    }

    protected function modelProvidesDataTableData(): bool
    {

        if (\is_a($this->dataTable->dataSrc, ProvidesDataTableData::class, true)) {
            return true;
        }

        return $this->modelSatisfiesProvidesDataTableDataInterface();
    }

    protected function modelSatisfiesProvidesDataTableDataInterface(): bool
    {
        if (!\method_exists($this->dataTable->dataSrc, 'dataTableData')) {
            return false;
        }

        $method = new \ReflectionMethod("{$this->dataTable->dataSrc}::dataTableData");

        $parameters = $method->getParameters();

        if (count($parameters) === 0) {
            return false;
        }

        $firstParam = $parameters[0];
        $firstParamType = $firstParam->getType();

        if (!$firstParamType instanceof ReflectionNamedType) {
            return false;
        }

        if ($firstParamType->getName() !== LwDataRetrievalParams::class) {
            return false;
        }

        foreach (\array_slice($parameters, 1) as $param) {
            if (!$param->isOptional()) {
                return false;
            }
        }

        $returnType = $method->getReturnType();

        if (!$returnType) {
            return false;
        }

        $allowedReturnTypes = [
            Paginator::class,
            LengthAwarePaginator::class,
            CursorPaginator::class,
            Collection::class,
            self::ITERABLE_PSEUDOTYPE,
        ];

        $typesToCheck = [];

        if ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $type) {
                $typesToCheck[] = $type->getName();
            }
        } elseif ($returnType instanceof ReflectionNamedType) {
            $typesToCheck[] = $returnType->getName();
        } else {
            return false;
        }

        foreach ($typesToCheck as $type) {
            if (!in_array($type, $allowedReturnTypes, true)) {
                return false;
            }
        }

        return true;
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
    }

    protected function applyDataTableColumnsSearchOnEloquentQuery(EloquentBuilder $query, ?array $columnsSearch)
    {
        if (empty($columnsSearch)) {
            return;
        }

        if (\is_a($this->dataTable->dataSrc, SearchesDataTableColumns::class, true)) {
            $model = $this->dataTable->dataSrc;
            (new $model())->applyDataTableColumnsSearchToQuery($query, $columnsSearch);

            return;
        }

        foreach ($columnsSearch as $dataField => $value) {
            $query->whereLike($dataField, "%$value%");
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
