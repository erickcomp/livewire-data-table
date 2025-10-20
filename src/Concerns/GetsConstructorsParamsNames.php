<?php

namespace ErickComp\LivewireDataTable\Concerns;

use Illuminate\View\ComponentAttributeBag;
use Illuminate\Support\Str;

trait GetsConstructorsParamsNames
{
    /**
     * Get the contructor prameters names for the given class
     * 
     * @return string[]
     */
    protected static function getConstructorsParams(string|object $class): array
    {
        if (\is_object($class)) {
            $class = $class::class;
        }

        $reflMethod = new \ReflectionMethod("$class::__construct");

        return \array_map(fn(\ReflectionParameter $reflParam) => $reflParam->getName(), $reflMethod->getParameters());
    }

    protected static function extractActionConstructorParamsFromAttributes(string $class, ComponentAttributeBag $attributes, $makeAttributesNewKeysCamelCase = false): array
    {
        $constructorParams = static::getConstructorsParams($class);

        $constructorParamsValues = [];
        $attributesNewValues = [];

        foreach ($attributes->all() as $attrKey => $attrVal) {
            $camelKey = Str::camel($attrKey);

            if (\in_array($camelKey, $constructorParams)) {
                $constructorParamsValues[$camelKey] = $attrVal;
            } elseif ($makeAttributesNewKeysCamelCase) {
                $attributesNewValues[$camelKey] = $attrVal;
            } else {
                $attributesNewValues[$attrKey] = $attrVal;
            }
        }

        if (\in_array('attributes', $constructorParams)) {
            $constructorParamsValues['attributes'] = $attributes;
        }

        $attributes->setAttributes($attributesNewValues);

        return [
            'params' => $constructorParams,
            'paramsValues' => $constructorParamsValues,
        ];
    }
}
