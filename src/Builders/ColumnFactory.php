<?php

namespace ErickComp\LivewireDataTable\Builders;

use ErickComp\LivewireDataTable\Builders\Column\ActionsColumn;
use ErickComp\LivewireDataTable\Builders\Column\BaseColumn;
use ErickComp\LivewireDataTable\Builders\Column\CustomRenderedColumn;
use ErickComp\LivewireDataTable\Builders\Column\DataColumn;
use ErickComp\LivewireDataTable\Concerns\GetsConstructorsParamsNames;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Column;
use Illuminate\View\ComponentAttributeBag;

class ColumnFactory
{
    use GetsConstructorsParamsNames;

    public static function make(
        //DataTable $__dataTable,
        //ComponentAttributeBag $attributes,
        Column $columnComponent,

    ): BaseColumn {

        $attributes = $columnComponent->attributes;

        if ($columnComponent->isCustomClassColumn()) {
            $class = $attributes->get('class');

            $extracted = static::extractActionConstructorParamsFromAttributes($class, $attributes);
            $constructorParamsValues = $extracted['paramsValues'];

            if (\in_array('__dataTableColumn', $extracted['params'])) {
                $constructorParamsValues['__dataTableColumn'] = $columnComponent;
            }

            return app()->make($class, $constructorParamsValues);
        }

        if ($columnComponent->isActionsColumn()) {
            $extracted = static::extractActionConstructorParamsFromAttributes(ActionsColumn::class, $attributes);
            $constructorParamsValues = $extracted['paramsValues'];
            $constructorParamsValues['actions'] = $columnComponent->getActions();

            return app()->make(ActionsColumn::class, $constructorParamsValues);
        }

        if ($columnComponent->isCustomRenderedCodeColumn()) {

            $extracted = static::extractActionConstructorParamsFromAttributes(CustomRenderedColumn::class, $attributes);
            $constructorParamsValues = $extracted['paramsValues'];
            $constructorParamsValues['customRendererCode'] = $columnComponent->customRendererCode;

            return app()->make(CustomRenderedColumn::class, $constructorParamsValues);
        }

        $extracted = static::extractActionConstructorParamsFromAttributes(DataColumn::class, $attributes);
        $constructorParamsValues = $extracted['paramsValues'];

        return app()->make(DataColumn::class, $constructorParamsValues);
    }
}
