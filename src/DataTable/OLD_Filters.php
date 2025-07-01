<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\DataTable\BaseDataTableComponent;
use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;

class OLD_Filters extends BaseDataTableComponent
{
    use FillsComponentAttributeBags {
        fillComponentAttributeBags as public;
    }

    public ComponentAttributeBag $containerAttributes;
    public ComponentAttributeBag $filterRowAttributes;
    public ComponentAttributeBag $filterWrapperAttributes;

    public Filters $__dataTableFilters;

    public function __construct(
        public int $rowLength = 4,
        public bool $collapsible = true,
    ) {
        $this->viewData['__dataTableFilters'] = $this;

        $this->containerAttributes = new ComponentAttributeBag();
        $this->filterRowAttributes = new ComponentAttributeBag();
        $this->filterWrapperAttributes = new ComponentAttributeBag();
    }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'containerAttributes', //default
            'filter-row-' => 'filterRowAttributes',
            'filter-wrapper-' => 'filterWrapperAttributes',
        ];
    }
}
