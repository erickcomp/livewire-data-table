<?php

namespace ErickComp\LivewireDataTable\DataTable;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Arr;

class DataColumn extends Column
{
    public function __construct(
        string $title,
        string $dataField,
        //?string $name = null,
        bool|string $searchable = false,
        bool $sortable = false,
    ) {
        parent::__construct($title, $dataField, /*$name,*/ $searchable, $sortable);
    }

    /**
     * The value of this column's data field on the given row.
     *
     * Same lookup as data_get(), except that a field that exists and holds null comes back as null instead
     * of being reported as missing. data_get() walks objects with isset(), which is false both for a null
     * property and for a null Eloquent attribute, so any nullable column holding null used to abort the
     * rendering of the whole table.
     *
     * @throws \LogicException when the row really has no such field
     */
    public function getCellData(mixed $row, int $rowNumber): mixed
    {
        $noData = new \stdClass();
        $value = \data_get($row, $this->dataField, $noData);

        if ($value !== $noData) {
            return $value;
        }

        if ($this->rowHoldsNullAlongDataField($row)) {
            return null;
        }

        throw new \LogicException("Cannot get data for column [{$this->dataField}] on row #{$rowNumber}");
    }

    /**
     * Walks the (dotted) data field the way data_get() does and tells whether the walk stops at a field that
     * exists with a null value, as opposed to a field that is not there at all. A null in the middle of the
     * path (an optional relation that is not set, for instance) counts as a null value too.
     *
     * Eloquent models are checked before the generic ArrayAccess branch on purpose: Model implements
     * ArrayAccess, and its offsetExists() is false for null attributes — the very case being handled here.
     */
    protected function rowHoldsNullAlongDataField(mixed $row): bool
    {
        $target = $row;

        foreach (\explode('.', $this->dataField) as $segment) {
            if ($target instanceof EloquentModel) {
                if (!$this->modelHasField($target, $segment)) {
                    return false;
                }

                $target = $target->getAttribute($segment);
            } elseif (Arr::accessible($target)) {
                if (!Arr::exists($target, $segment)) {
                    return false;
                }

                $target = $target[$segment];
            } elseif (\is_object($target)) {
                if (!\property_exists($target, $segment)) {
                    return false;
                }

                $target = $target->{$segment};
            } else {
                return false;
            }

            if ($target === null) {
                return true;
            }
        }

        return false;
    }

    protected function modelHasField(EloquentModel $model, string $field): bool
    {
        return \array_key_exists($field, $model->getAttributes())
            || $model->relationLoaded($field)
            || $model->isRelation($field)
            || $model->hasGetMutator($field)
            || $model->hasAttributeGetMutator($field);
    }
}
