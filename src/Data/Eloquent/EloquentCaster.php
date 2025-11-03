<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use ErickComp\LivewireDataTable\Data\ValuesCaster;
use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Date;

class EloquentCaster extends ValuesCaster
{
    public static function castValueFromFilterUsingModel(EloquentModel|EloquentBuilder $model, array $filter, ?string $range = null)
    {
        if ($model instanceof EloquentBuilder) {
            $model = $model->getModel();
        }

        $value = static::getRawValueFromFilter($filter, $range);

        if ($model->hasCast($filter['column'])) {
            return static::callEloquentCastAttribute($model, $filter['column'], $value);
        }

        return static::castValueFromFilter($filter, $range);
    }

    protected static function callEloquentCastAttribute(EloquentModel $modelInstance, $attribute, mixed $value): mixed
    {
        static $caster = null;

        if ($caster === null) {
            $caster = new class extends EloquentModel {
                public function __invoke(EloquentModel $model, string $attribute, mixed $value): mixed
                {
                    // in PHP, sibling classes have access to protected stuff (which were defined on parent) of sibling classes
                    return $model->castAttribute($attribute, $value);
                }
            };
        }

        return $caster($modelInstance, $attribute, $value);
    }
}
