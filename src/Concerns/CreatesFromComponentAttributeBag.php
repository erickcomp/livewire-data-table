<?php

namespace ErickComp\LivewireDataTable\Concerns;

use Illuminate\View\ComponentAttributeBag;

trait CreatesFromComponentAttributeBag
{
    use GetsConstructorsParamsNames;

    protected ComponentAttributeBag $attributes;

    /**
     * Calls the constructor using data from the provided instance of \Illuminate\View\ComponentAttributeBag,
     * extracting only the entries that match the constructor parameters.
     * 
     * Additional arguments can be provided. In such cases, the additional arguments MUST be named—
     * either by destructuring string-keyed arrays or by using named parameters.
     * 
     * @throws \BadMethodCallException
     */
    public static function fromComponentAttributeBag(ComponentAttributeBag $attributes, ...$extraArgs): static
    {
        $constructorParams = static::extractActionConstructorParamsFromAttributes(static::class, $attributes);

        foreach ($extraArgs as $argName => $argVal) {
            if (!\is_string($argName)) {
                throw new \BadMethodCallException('The data-field attribute is required for searchable or sortable columns.');
            }

            $constructorParams['paramsValues'][$argName] = $argVal;
        }

        $instance = app()->make(static::class, $constructorParams['paramsValues']);
        $instance->attributes = $attributes;

        return $instance;
    }
}
