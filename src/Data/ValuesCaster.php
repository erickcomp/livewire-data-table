<?php

namespace ErickComp\LivewireDataTable\Data;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

class ValuesCaster
{
    public const AS_STRING = 0;
    public const AS_NUMBER = 1;
    public const AS_DATETIME = 2;
    
    public static function castValueToFilterType(mixed $rawValue, string $filterType): mixed
    {
        /*
        public const TYPE_TEXT = 'text';
        public const TYPE_NUMBER = 'number';
        public const TYPE_NUMBER_RANGE = 'number-range';
        public const TYPE_DATE = 'date';
        public const TYPE_DATE_PICKER = 'date-picker';
        public const TYPE_DATETIME = 'datetime';
        public const TYPE_DATETIME_PICKER = 'datetime-picker';
        public const TYPE_SELECT = 'select';
        public const TYPE_SELECT_MULTIPLE = 'select-multiple';
        */
        return match ($filterType) {
            Filter::TYPE_TEXT => (string) $rawValue,
            Filter::TYPE_NUMBER, Filter::TYPE_NUMBER_RANGE => static::tryToParseAsNumber($rawValue),
            Filter::TYPE_DATE, Filter::TYPE_DATE_PICKER, Filter::TYPE_DATETIME, Filter::TYPE_DATETIME_PICKER => static::tryToParseAsDatetime($rawValue),
            default => throw new \UnexpectedValueException('Invalid filter type: [' . $filterType . ']'),
        };
    }


    public static function castValueFromFilter(array $filter, ?string $range = null)
    {
        // // if ($range !== null && !\in_array(\strtolower($range), ['from', 'to'])) {
        // //     throw new \LogicException("Invalid range: $range. The valid values for the \$range parameter are: \"from\", \"to\"");
        // // }

        // $value = $range !== null ? $filter['value'][$range] : $filter['value'];

        // $castedValue = match($filter['type']) {
        //     Filter::TYPE_NUMBER => static::cast
        // }

        // return static::tryToCastFromDateFilterTypes($filter, $value);

    }

    protected static function getRawValueFromFilter(array $filter, ?string $range = null)
    {
        if ($range !== null && !\in_array(\strtolower($range), ['from', 'to'])) {
            throw new \LogicException("Invalid range: $range. The valid values for the \$range parameter are: \"from\", \"to\"");
        }

        return $range !== null
            ? $filter['value'][$range]
            : $filter['value'];
    }

    protected static function castByFilterType($filter, $value)
    {
        // match($filter['type']) {
        //     Filter::TYPE
        // }
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
                $parsed = Date::parse($value);
            } catch (\Throwable $t) {
                Log::warning("erickcomp/livewire-data-table: Could not convert value [$value] to a Date/Datetime instance");
                $parsed = $value;
            }
        }

        return $parsed;
    }

    protected static function tryToParseAsDatetime($rawValue)
    {
        try {
            $parsed = Date::parse($rawValue);
        } catch (\Throwable $t) {
            Log::warning("erickcomp/livewire-data-table: Could not convert value [$rawValue] to a Date/Datetime instance");
            $parsed = $rawValue;
        }

        return $parsed;
    }

    protected static function tryToParseAsNumber($rawValue)
    {
        $parsed = \filter_var($rawValue, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE);

        if (\is_int($parsed)) {
            return $parsed;
        }

        $parsed = \filter_var($rawValue, \FILTER_VALIDATE_FLOAT, \FILTER_NULL_ON_FAILURE);

        if (\is_int($parsed)) {
            return $parsed;
        }

        Log::warning("erickcomp/livewire-data-table: Could parse value [$rawValue] as a number (int or float)");

        return $rawValue;
    }
}
