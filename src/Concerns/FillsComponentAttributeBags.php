<?php

namespace ErickComp\LivewireDataTable\Concerns;

use Illuminate\View\ComponentAttributeBag;

trait FillsComponentAttributeBags
{
    abstract protected function getAttributeBagsMappings(): array;
    protected function fillComponentAttributeBags(ComponentAttributeBag $attributes)
    {
        $attributeBagsMappings = $this->getAttributeBagsMappings();

        $defaultBagAttributes = [];
        $partitionedAttributes = [];

        foreach ($attributes->all() as $attrName => $attrVal) {
            $found = false;

            foreach ($attributeBagsMappings as $prefix => $bag) {
                if ($prefix === 0) {
                    continue;
                }

                if (\str_starts_with($attrName, $prefix)) {
                    $partitionedAttributes[$bag][\substr($attrName, \strlen($prefix))] = $attrVal;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $defaultBagAttributes[$attrName] = $attrVal;
            }
        }

        // Set attributes to their respective bags
        foreach ($partitionedAttributes as $bagName => $bagAttributes) {
            if (!isset($this->{$bagName}) && \property_exists($this, $bagName)) {
                $this->{$bagName} = new ComponentAttributeBag($bagAttributes);
            } else {
                $this->{$bagName}->setAttributes($bagAttributes);
            }
        }

        if (!isset($this->{$attributeBagsMappings[0]}) && \property_exists($this, $attributeBagsMappings[0])) {
            $this->{$attributeBagsMappings[0]} = new ComponentAttributeBag($defaultBagAttributes);
        } else {
            $this->{$attributeBagsMappings[0]}->setAttributes($defaultBagAttributes);
        }

        foreach ($attributeBagsMappings as $bag) {
            if (!isset($this->{$bag}) && \property_exists($this, $bag)) {
                $this->{$bag} = new ComponentAttributeBag();
            }
        }
    }
}
