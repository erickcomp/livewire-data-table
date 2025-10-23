<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class EloquentDataSource extends QueryBuilderDataSource
{
    private const ITERABLE_PSEUDOTYPE = 'iterable';
    
    /** @var class-string<EloquentModel> */
    protected string $modelClass;

    // public function __construct(
    //     protected DataTable $dataTable,
    // ) {
    //     if (!\is_a($dataTable->dataSrc, Model::class, true)) {
    //         throw new \LogicException("The value [{$dataTable->dataSrc}] is not an Eloquent Model class");
    //     }
    // }

    /**
     * @param class-string<EloquentModel> $modelClass
     * 
     * @return EloquentBuilder
     */
    public function __construct(string $modelClass,) {
        if (!\is_a($modelClass, EloquentModel::class, true)) {
            throw new \LogicException("The value [$modelClass] is not an Eloquent Model class");
        }

        parent::__construct(\is_a($modelClass, )

        );
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
    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator
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

        if (!$firstParamType instanceof \ReflectionNamedType) {
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
            \Traversable::class,
            'array',
        ];

        $typesToCheck = [];

        if ($returnType instanceof \ReflectionUnionType) {
            foreach ($returnType->getTypes() as $type) {
                $typesToCheck[] = $type->getName();
            }
        } elseif ($returnType instanceof \ReflectionNamedType) {
            $typesToCheck[] = $returnType->getName();
        } else {
            return false;
        }

        foreach ($typesToCheck as $type) {
            $found = false;
            foreach ($allowedReturnTypes as $allowedReturnType) {
                if ($type === $allowedReturnType || \is_a($type, $allowedReturnType, true)) {
                    $found = true;

                    break;
                }
            }

            if (!$found) {
                return false;
            }
        }

        return true;
    }
}
