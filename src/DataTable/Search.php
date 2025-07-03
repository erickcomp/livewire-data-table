<?php

namespace ErickComp\LivewireDataTable\DataTable;

use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;

class Search
{
    use FillsComponentAttributeBags;

    protected array $defaultContainerAttributes = [
        //'row-length' => 4,
        //'collapsible' => true,
        'class' => 'lw-dt-table-search',
    ];

    protected array $defaultInputAttributes = [
        'id' => '',
        'class' => 'lw-dt-filters-row',
        'x-on:keydown.enter' => 'applySearch()',
        'x-model' => 'dtData()[\'inputSearch\']',
    ];

    protected array $defaultButtonAttributes = [
        'class' => 'lw-dt-filter-item',
    ];

    public ComponentAttributeBag $containerAttributes;
    public ComponentAttributeBag $inputAttributes;
    public ComponentAttributeBag $buttonAttributes;

    /** @var string[] */
    public array $dataFields;

    public function __construct(?ComponentAttributeBag $componentAttributes = null)
    {
        if ($componentAttributes !== null) {
            $this->setup($componentAttributes);
        }
    }

    public function setup(ComponentAttributeBag $componentAttributes)
    {
        $this->fillComponentAttributeBags($componentAttributes);

        $this->containerAttributes = $this->containerAttributes->merge($this->defaultContainerAttributes);
        $this->inputAttributes = $this->inputAttributes->merge($this->defaultInputAttributes);
    }

    public function isSearchable(): bool
    {
        return isset($this->dataFields) && !empty($this->dataFields);
    }

    public function buildId(string $fallbackPrefix)
    {
        
    }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'containerAttributes',
            'input-' => 'inputAttributes',
            'apply-search-button-' => 'buttonAttributes',
        ];
    }
}
