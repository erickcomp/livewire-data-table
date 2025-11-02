<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\Support\Facades\Date;

class EloquentCaster extends ParamValuesCaster
{
    public static function castValueFromFilter(array $filter, ?string $range = null)
    {
        if ($range !== null && !\in_array(\strtolower($range), ['from', 'to'])) {
            throw new \LogicException("Invalid range: $range. The valid values for the \$range parameter are: \"from\", \"to\"");
        }

        if ($model instanceof EloquentBuilder) {
            $model = $model->getModel();
        }

        $value = $range !== null ? $filter['value'][$range] : $filter['value'];

        if ($model->hasCast($filter['column'])) {
            // in PHP, sibling classes have access to protected stuff (which were defined on parent) of sibling classes
            return $model->castAttribute($filter['column'], $value);
        }

        return static::tryToCastFromDateFilterTypes($filter, $value);

    }
}
