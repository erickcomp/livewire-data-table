<?php

namespace ErickComp\LivewireDataTable\Data\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\Support\Facades\Date;

class ParamValuesCaster extends EloquentModel
{
    public static function castValueFromFilter(array $filter, ?string $range = null)
    {
        if ($range !== null && !\in_array(\strtolower($range), ['from', 'to'])) {
            throw new \LogicException("Invalid range: $range. The valid values for the \$range parameter are: \"from\", \"to\"");
        }

        $value = $range !== null ? $filter['value'][$range] : $filter['value'];

        $casted = match($filter['type']) {
            Filter::TYPE
        }

        return static::tryToCastFromDateFilterTypes($filter, $value);

    }

    protected static castByFilterType($filter, $value)
    {
        match($filter['type']) {
            Filter::TYPE
        }
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

            try {
                $parsed =  Date::parse($value);
            } catch (\Throwable $t) {
                Log::warning("erickcomp/livewire-data-table: Could not convert value [$value] to a Date instance")
                $parsed = $value;
            }
            
        }

        return $parsed;
    }
}
