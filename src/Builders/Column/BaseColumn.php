<?php

namespace ErickComp\LivewireDataTable\Builders\Column;

use ErickComp\LivewireDataTable\DataTable;
use Illuminate\View\ComponentAttributeBag;
use Nette\NotImplementedException;

class BaseColumn
{
    public ComponentAttributeBag $thAttributes;
    public ComponentAttributeBag $thSearchAttributes;
    public ComponentAttributeBag $tdAttributes;

    public function __construct(
        //public DataTable $__dataTable,
        public string $name,
        public string $title,
        public ComponentAttributeBag $attributes,
        public bool $searchable = false,
        public bool $sortable = false,
    ) {
        $this->initComponentAttributeBags();
        $this->fillComponentAttributeBags($attributes);
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    protected function initComponentAttributeBags()
    {
        $this->thAttributes = new ComponentAttributeBag();
        $this->thSearchAttributes = new ComponentAttributeBag();
        $this->tdAttributes = new ComponentAttributeBag();
    }

    protected function fillComponentAttributeBags(ComponentAttributeBag $attributes)
    {
        $thAttributes = [];
        $thSearchAttributes = [];
        $tdAttributes = [];

        foreach ($attributes->all() as $attrName => $attrVal) {

            if (\str_starts_with($attrName, 'th-')) {
                $thAttributes[$attrName] = $attrVal;
            } elseif (\str_starts_with($attrName, 'th-search-')) {
                $thSearchAttributes[$attrName] = $attrVal;
            } else {
                $tdAttributes[$attrName] = $attrVal;
            }
        }

        $this->thAttributes->setAttributes($thAttributes);
        $this->thSearchAttributes->setAttributes($thSearchAttributes);
        $this->tdAttributes->setAttributes($tdAttributes);
    }
}
