<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\DataTable\BaseDataTableComponent;
use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use Illuminate\Support\Facades\Blade;

class Filters
{
    use FillsComponentAttributeBags;

    protected array $defaultContainerAttributes = [
        //'row-length' => 4,
        'collapsible' => true,
        //'class' => 'lw-dt-filters-container',
    ];

    protected array $defaultFilterRowAttributes = [
        'class' => 'lw-dt-filters-row',
    ];

    protected array $defaultFilterItemsAttributes = [
        'class' => 'lw-dt-filter-item',
    ];

    public string $title {
        get => $this->containerAttributes['title'] ?? \trans('Filters');
    }

    public bool $collapsible {
        get => \filter_var($this->containerAttributes['collapsible'], \FILTER_VALIDATE_BOOL);
    }

    public ComponentAttributeBag $containerAttributes;
    public ComponentAttributeBag $filterRowAttributes;
    public ComponentAttributeBag $filterItemsAttributes;

    /** @var Filter[] */
    public array $filtersItems;

    public function __construct(ComponentAttributeBag $componentAttributes)
    {
        $this->fillComponentAttributeBags($componentAttributes);

        $this->containerAttributes = $this->containerAttributes->merge($this->defaultContainerAttributes);
        $this->containerAttributes = $this->containerAttributes->class(['lw-dt-filters-container', 'hide', 'collapsible' => $this->collapsible]);
        $this->filterRowAttributes = $this->filterRowAttributes->merge($this->defaultFilterRowAttributes);
        $this->filterItemsAttributes = $this->filterItemsAttributes->merge($this->defaultFilterItemsAttributes);
    }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'containerAttributes', //default
            'filter-row-' => 'filterRowAttributes',
            'filter-item-' => 'filterItemsAttributes',
        ];
    }
}
