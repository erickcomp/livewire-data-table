<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Data\Eloquent\CustomizesDataTableQuery;
use ErickComp\LivewireDataTable\Data\Eloquent\CustomizesDataTableResults;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class EloquentDataSource extends EloquentBuilderDataSource
{
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
    public function __construct(
        string $modelClass,
        DataSourcePaginationType $paginationType,
    ) {
        if (!\is_a($modelClass, EloquentModel::class, true)) {
            throw new \LogicException("The value [$modelClass] is not an Eloquent Model class");
        }

        $this->modelClass = $modelClass;

        $query = \is_a($modelClass, CustomizesDataTableQuery::class, true)
            ? (app()->make($modelClass))->dataTableQuery()
            : $this->newQuery();

        
        parent::__construct($query, $paginationType);
    }

    public function __serialize(): array
    {
        return [
            ...parent::__serialize(),
            ...['modelClass' => $this->modelClass],
        ];
    }


    public function __unserialize(array $data)
    {
        parent::__unserialize($data);
        $this->modelClass = $data['modelClass'];
    }
    public function getQuery(LwDataRetrievalParams $params): EloquentBuilder
    {
        return $this->applyDataRetrievalParamsOnQuery($this->newQuery(), $params);
    }

    /**
     * Returns paginated data using the provided eloquent model class
     * 
     * @param class-string<Model> $class
     * @param \ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams $params
     * @return void
     */
    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection
    {
        if ($this->modelProvidesDataTableData()) {
            return $this->makeModel()->dataTableData($params);
        }

        return match ($this->paginationType) {
            DataSourcePaginationType::None => $this->getQuery($params)->get(),
            DataSourcePaginationType::LengthAware => $this->getQuery($params)->paginate(perPage: $params->perPage, pageName: $params->pageName, page: $params->page),
            DataSourcePaginationType::Cursor => $this->getQuery($params)->cursorPaginate(perPage: $params->perPage, cursorName: $params->pageName),
            DataSourcePaginationType::Simple => $this->getQuery($params)->simplePaginate(perPage: $params->perPage, pageName: $params->pageName, page: $params->page),
        };
    }

    /**
     * @param class-string<EloquentModel>  $modelClass
     */
    protected function makeModel(): EloquentModel
    {
        return app()->make($this->modelClass);
    }

    protected function newQuery(): EloquentBuilder
    {
        return $this->makeModel()->newQuery();
    }

    protected function modelProvidesDataTableData(): bool
    {

        if (\is_a($this->modelClass, CustomizesDataTableResults::class, true)) {
            return true;
        }

        return $this->modelSatisfiesCustomizesDataTableResultsInterface();
    }

    protected function modelSatisfiesCustomizesDataTableResultsInterface(): bool
    {
        if (!\method_exists($this->modelClass, 'dataTableData')) {
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
