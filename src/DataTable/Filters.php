<?php

namespace ErickComp\LivewireDataTable\DataTable;

use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;

class Filters
{
    use FillsComponentAttributeBags;

    protected array $defaultContainerAttributes = [
        //'row-length' => 4,
        //'class' => 'lw-dt-filters-container',
        'collapsible' => '',
        'x-show' => "filtersContainerIsOpen",
        'x-transition.scale.origin.top' => '',
        'x-transition:enter.duration.200ms' => '',
        'x-transition:leave.duration.270ms' => '',
    ];

    protected array $defaultButtonToggleAttributes = [
        'class' => 'filters-toggle-button',
        //'x-show' => "filtersContainerIsOpen",
        //'x-transition' => '',
        'x-on:click' => "toggleFiltersContainer",
    ];

    protected array $defaultButtonApplyAttributes = [
        'class' => 'filters-apply-button',
        'x-on:click' => 'applyFilters()',
    ];

    protected array $defaultFilterItemsAttributes = [
        'class' => 'lw-dt-filter-item',
    ];

    // public string $title {
    //     get => $this->containerAttributes['title'] ?? __('erickcomp_lw_data_table::messages.filters_container_label');
    // }

    // public bool $collapsible {
    //     get => \filter_var($this->containerAttributes['collapsible'], \FILTER_VALIDATE_BOOL);
    // }

    // public bool $filtersToggleNoDefaultIcon {
    //     get {
    //         return \filter_var($this->containerAttributes['filters-toggle-no-default-icon'], \FILTER_VALIDATE_BOOL);
    //     }
    // }

    public ComponentAttributeBag $containerAttributes;
    public ComponentAttributeBag $buttonToggleAttributes;
    public ComponentAttributeBag $buttonApplyAttributes;
    public ComponentAttributeBag $filterItemsAttributes;

    /** @var Filter[] */
    public array $filtersItems;

    public function __construct(ComponentAttributeBag $componentAttributes)
    {
        $this->fillComponentAttributeBags($componentAttributes);

        $this->containerAttributes = $this->containerAttributes->merge($this->defaultContainerAttributes);
        $this->containerAttributes = $this->containerAttributes->class(['lw-dt-filters-container', 'collapsible' => $this->isCollapsible()]);

        $this->buttonToggleAttributes = $this->buttonToggleAttributes->merge($this->defaultButtonToggleAttributes);
        $this->buttonApplyAttributes = $this->buttonApplyAttributes->merge($this->defaultButtonApplyAttributes);

        $this->filterItemsAttributes = $this->filterItemsAttributes->merge($this->defaultFilterItemsAttributes);
    }

    public function containerAttributes(): ComponentAttributeBag
    {
        return $this->containerAttributes->except([
            'collapsible',
            'filters-toggle-no-default-icon',
        ]);
    }

    public function title(): string
    {
        return $this->containerAttributes['title'] ?? __('erickcomp_lw_data_table::messages.filters_container_label');
    }
    public function isCollapsible(): bool
    {
        return \filter_var($this->containerAttributes['collapsible'], \FILTER_VALIDATE_BOOL);
    }

    public function shouldShowDefaultIconOnToggleButton(): bool
    {
        return !\filter_var($this->containerAttributes['filters-toggle-no-default-icon'], \FILTER_VALIDATE_BOOL);
    }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'containerAttributes', //default
            'button-toggle-' => 'buttonToggleAttributes',
            'button-apply-' => 'buttonApplyAttributes',
            'filter-item-' => 'filterItemsAttributes',
        ];
    }
}
