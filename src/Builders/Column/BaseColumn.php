<?php

namespace ErickComp\LivewireDataTable\Builders\Column;

use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use ErickComp\LivewireDataTable\DataTable;
use Illuminate\View\ComponentAttributeBag;
use Nette\NotImplementedException;

class BaseColumn
{
    use FillsComponentAttributeBags;

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

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'tdAttributes', // default
            'th-search-' => 'thSearchAttributes',
            'th-' => 'thAttributes',
        ];
    }
}
