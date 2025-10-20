<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class EloquentDataGetter
{
    /**
     * Returns paginated data using the provided eloquent model class
     * 
     * @param class-string<Model> $class
     * @param \ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams $params
     * @return void
     */
    public static function getData(string $modelClass, LwDataRetrievalParams $params): LengthAwarePaginator
    {
        if (\is_a($modelClass, ProvidesDataTableData::class, true)) {
            $model = app()->make($modelClass);

            return $model->dataTableData($params);
        }

        return static::applyDataRetrievalParamsToQuery($modelClass::query(), $params)->paginate();
    }

    protected static function providesDataTableData(string $modelClass): bool
    {
        if (\is_a($modelClass, ProvidesDataTableData::class, true)) {
            return true;
        }

        if (!\method_exists($modelClass, 'dataTableData')) {
            return false;
        }

        $reflMethod = new \ReflectionMethod("$modelClass::dataTableData");

        foreach () {}
    }

    protected static function applyDataRetrievalParamsToQuery(EloquentBuilder $query, LwDataRetrievalParams $params): EloquentBuilder
    {
        return $query;
    }
}
