<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Livewire\Preset;
use Illuminate\Support\Facades\Log;
use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;

class Filters
{
    use FillsComponentAttributeBags;

    protected bool $presetCollapsible;

    protected array $defaultContainerAttributes = [
        //'row-length' => 4,
        //'class' => 'lw-dt-filters-container',
        //'collapsible' => '',
        'x-show.important' => "filtersContainerIsOpen",
        //'x-transition.scale.origin.top' => '',
        //'x-transition:enter.duration.200ms' => '',
        //'x-transition:leave.duration.270ms' => '',
    ];

    protected array $defaultButtonToggleAttributes = [
        //'class' => 'filters-toggle-button',
        //'x-show' => "filtersContainerIsOpen",
        //'x-transition' => '',
        //'x-on:click' => "toggleFiltersContainer",
    ];

    protected array $defaultButtonApplyAttributes = [
        //'class' => 'filters-apply-button',
        //'x-on:click' => 'applyFilters()',
    ];

    protected array $defaultFilterItemsAttributes = [
        //'class' => 'lw-dt-filter-item',
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
    public array $filtersItems = [];

    public function __construct(ComponentAttributeBag $componentAttributes, Preset $preset)
    {
        $this->presetCollapsible = $preset->get('filters.collapsible', true);
        $this->fillComponentAttributeBags($componentAttributes);

        $this->containerAttributes = $this->containerAttributes->merge($this->defaultContainerAttributes);
        $this->containerAttributes = $this->containerAttributes->class(['lw-dt-filters-container', 'collapsible' => $this->isCollapsible()]);

        $this->buttonToggleAttributes = $this->buttonToggleAttributes->merge($this->defaultButtonToggleAttributes);
        $this->buttonApplyAttributes = $this->buttonApplyAttributes->merge($this->defaultButtonApplyAttributes);

        $this->filterItemsAttributes = $this->filterItemsAttributes->merge($this->defaultFilterItemsAttributes);
    }

    public function containerAttributes(Preset $preset): ComponentAttributeBag
    {
        $alpineTransition = \array_fill_keys($preset->get('filters.toggle-button.alpine-transition', []), '');
        //$collapsible = ['collapsible' => $this->isCollapsible()];

        return $this->containerAttributes->except(['collapsible', 'filters-toggle-no-icon',])
            ->merge($alpineTransition)
            //->merge($collapsible)
            ->merge(['x-cloak' => $this->isCollapsible()])
            ->class($preset->get('filters.container.class', []));
    }

    public function title(): string
    {
        return $this->containerAttributes['title'] ?? __('erickcomp_lw_data_table::messages.filters_container_label');
    }
    public function isCollapsible(): bool
    {
        if ($this->containerAttributes->has('collapsible')) {
            $attributeValue = \filter_var($this->containerAttributes->get('collapsible'), \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE);

            if (\is_bool($attributeValue)) {
                return $attributeValue;
            }

            $errmsg = \sprintf(
                'erickcomp/livewire-data-table: Filter attribute "collapsible" has an invalid boolean value (%s). The value must be parseable by filter_var() using FILTER_VALIDATE_BOOL. Using the value from the preset.',
                \var_export($attributeValue, true),
            );
            Log::notice($errmsg);
        }

        return $this->presetCollapsible;
    }

    public function shouldShowIconOnToggleButton(): bool
    {
        return !\filter_var($this->containerAttributes['filters-toggle-no-icon'], \FILTER_VALIDATE_BOOL);
    }

    public function getToggleButtonAttributes(Preset $preset, bool $shouldShowFiltersContainer): ComponentAttributeBag
    {
        return $this->buttonToggleAttributes->class([...$preset->get('filters.toggle-button.class', []), 'active' => $shouldShowFiltersContainer]);
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
