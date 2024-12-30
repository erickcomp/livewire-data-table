<?php

namespace ErickComp\LivewireDataTable\Builders;

use ErickComp\LivewireDataTable\Builders\Action\BaseAction;
use ErickComp\LivewireDataTable\Builders\Action\LinkAction;
use ErickComp\LivewireDataTable\Builders\Action\ServerAction;
use ErickComp\LivewireDataTable\Concerns\GetsConstructorsParamsNames;
use ErickComp\LivewireDataTable\DataTable\Action;
use ErickComp\LivewireDataTable\DataTable\Column;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\Support\Str;

class ActionFactory
{
    use GetsConstructorsParamsNames;

    public static function make(
        //Col $__dataTableColumn,
        //ComponentAttributeBag $attributes,
        Action $actionComponent,
    ): BaseAction {

        $attributes = $actionComponent->attributes;

        if ($attributes->has('class')) {
            $class = $attributes->get('class');
        } elseif ($attributes->has('action')) {
            $class = ServerAction::class;
        } elseif ($attributes->hasAny('route', 'url')) {
            $class = LinkAction::class;
        } else {
            throw new \LogicException("Could not find a column action class for the given arguments");
        }

        $extracted = static::extractActionConstructorParamsFromAttributes($class, $attributes);
        $constructorParamsValues = $extracted['paramsValues'];

        // if (\in_array('__dataTableColumn', $extracted['params'])) {
        //     $constructorParamsValues['__dataTableColumn'] = $__dataTableColumn;
        // }

        return app()->make($class, $constructorParamsValues);
    }


}
