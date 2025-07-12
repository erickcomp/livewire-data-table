<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use Illuminate\View\ComponentAttributeBag;

class Search
{
    use FillsComponentAttributeBags;

    protected array $defaultComponentAttributes = [
        //'class' => 'lw-dt-table-search',
    ];

    protected array $defaultInputAttributes = [
        'type' => 'text',
        'x-on:keydown.enter' => 'applySearch()',
        'x-model' => 'dtData()[\'inputSearch\']',
    ];

    protected array $defaultButtonAttributes = [
        //'class' => 'lw-dt-filter-item',
    ];

    public ComponentAttributeBag $componentAttributes;
    public ComponentAttributeBag $inputAttributes;
    public ComponentAttributeBag $buttonAttributes;
    /** @var string[]|true */
    public array|true $dataFields = true;
    public string $customRendererCode = '';

    public bool $initialized = false;

    public function __construct(?ComponentAttributeBag $componentAttributes = null)
    {
        $this->fillComponentAttributeBags($componentAttributes);

        $this->componentAttributes = $this->componentAttributes->merge($this->defaultComponentAttributes);
        $this->inputAttributes = $this->inputAttributes->merge($this->defaultInputAttributes);
        $this->buttonAttributes = $this->buttonAttributes->merge($this->defaultButtonAttributes);

        $this->setupDataFields();
    }

    public function hasDataFields(): bool
    {
        return isset($this->dataFields) && !empty($this->dataFields);
    }

    public function hasCustomRenderer(): bool
    {
        return !empty($this->customRendererCode);
    }

    public function shouldShowIconOnApplyButton()
    {
        return !$this->buttonAttributes->has('no-icon');
    }

    // public function setDataFieldsFromDataTable(DataTable $dataTable)
    // {
    //     $this->dataFields = [];

    //     foreach ($dataTable->columns as $col) {
    //         $this->dataFields[] = isset($col->dataField) ? $col->dataField : $col->name;
    //     }
    // }

    protected function setupDataFields()
    {
        if ($this->componentAttributes->has('data-fields')) {
            $dataFields = $this->componentAttributes['data-fields'];

            if (\is_string($dataFields) || $dataFields === true) {
                $dataFields = $dataFields === true || \strtolower($dataFields) === 'true'
                    ? true
                    : \array_map(trim(...), \explode(',', $dataFields));

            } elseif (!\is_array($dataFields)) {
                throw new \InvalidArgumentException('Attribute data-fields must be of type array|string|true, ' . \get_debug_type($dataFields) . ' given');
            }

            $this->dataFields = $dataFields;
            unset($this->componentAttributes['data-fields']);
        }
    }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'componentAttributes',
            'input-' => 'inputAttributes',
            'button-' => 'buttonAttributes',
        ];
    }
}
