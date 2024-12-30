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

        // Set attributes to respective bags
        foreach ($partitionedAttributes as $bagName => $bagAttributes) {
            $this->{$bagName}->setAttributes($bagAttributes);
        }

        $this->{$attributeBagsMappings[0]}->setAttributes($defaultBagAttributes);
    }
}
