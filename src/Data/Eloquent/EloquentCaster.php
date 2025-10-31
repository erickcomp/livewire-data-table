<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\Support\Facades\Date;

class EloquentCaster extends EloquentModel
{
    public static function castValueFromFilter(EloquentBuilder|EloquentModel $model, array $filter, ?string $range = null)
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

    protected static function tryToCastFromDateFilterTypes(array $filter, mixed $value)
    {
        $datetimeTypes = [
            Filter::TYPE_DATE,
            Filter::TYPE_DATE_PICKER,
            Filter::TYPE_DATETIME,
            Filter::TYPE_DATETIME_PICKER,
        ];

        if (\in_array($filter['type'], $datetimeTypes)) {
            return Date::parse($value);
        }

        return $value;
    }
}
