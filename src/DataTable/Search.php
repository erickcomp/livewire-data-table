<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Builders\Column\BaseColumn;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use ErickComp\LivewireDataTable\DataTable;
use Illuminate\View\ComponentAttributeBag;

class Search
{
    use FillsComponentAttributeBags;

    protected array $defaultContainerAttributes = [
        //'row-length' => 4,
        //'collapsible' => true,
        'class' => 'lw-dt-table-search',
    ];

    protected array $defaultInputAttributes = [
        'type' => 'text',
        'x-on:keydown.enter' => 'applySearch()',
        'x-model' => 'dtData()[\'inputSearch\']',
    ];

    protected array $defaultButtonAttributes = [
        'class' => 'lw-dt-filter-item',
    ];

    public ComponentAttributeBag $containerAttributes;
    public ComponentAttributeBag $inputAttributes;
    public ComponentAttributeBag $buttonAttributes;
    /** @var string[]|true */
    public array|true $dataFields = true;
    public string $customRendererCode = '';

    public bool $initialized = false;

    public function __construct(?ComponentAttributeBag $componentAttributes = null)
    {
        $this->fillComponentAttributeBags($componentAttributes);

        $this->containerAttributes = $this->containerAttributes->merge($this->defaultContainerAttributes);
        $this->inputAttributes = $this->inputAttributes->merge($this->defaultInputAttributes);
        $this->buttonAttributes = $this->buttonAttributes->merge($this->defaultButtonAttributes);
    }

    public function hasDataFields(): bool
    {
        return isset($this->dataFields) && !empty($this->dataFields);
    }

    public function hasCustomRenderer(): bool
    {
        return !empty($this->customRendererCode);
    }

    public function shouldRenderDefaultIconOnApplyButton()
    {
        return !$this->buttonAttributes->has('no-default-icon');
    }

    // public function setDataFieldsFromDataTable(DataTable $dataTable)
    // {
    //     $this->dataFields = [];

    //     foreach ($dataTable->columns as $col) {
    //         $this->dataFields[] = isset($col->dataField) ? $col->dataField : $col->name;
    //     }
    // }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'containerAttributes',
            'input-' => 'inputAttributes',
            'button-' => 'buttonAttributes',
        ];
    }
}
