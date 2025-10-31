<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use ErickComp\LivewireDataTable\Concerns\WorksWithTextSearchingAndFiltering;
use Illuminate\Support\Arr;
use Illuminate\View\ComponentAttributeBag;

class Search
{
    use FillsComponentAttributeBags;
    use WorksWithTextSearchingAndFiltering;

    public const SEARCH_MODE_CONTAINS = self::TEXT_MODE_CONTAINS;
    public const SEARCH_MODE_STARTS_WITH = self::TEXT_MODE_STARTS_WITH;
    public const SEARCH_MODE_ENDS_WITH = self::TEXT_MODE_ENDS_WITH;
    public const SEARCH_MODE_EXACT = self::TEXT_MODE_EXACT;
    public const SEARCH_MODE_FULLTEXT = self::TEXT_MODE_FULLTEXT;
    public const SEARCH_MODE_DEFAULT = self::SEARCH_MODE_CONTAINS;

    public const SEARCH_MODES = [
        self::SEARCH_MODE_EXACT,
        self::SEARCH_MODE_CONTAINS,
        self::SEARCH_MODE_STARTS_WITH,
        self::SEARCH_MODE_ENDS_WITH,
        self::SEARCH_MODE_FULLTEXT,
    ];

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

            if ($dataFields === true) {
                $dataFields = true;
            } elseif (\is_string($dataFields)) {
                if (\filter_var($dataFields, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) === true) {
                    $dataFields = true;
                } else {
                    $parsedDataFields = [];
                    foreach (\explode(',', $dataFields) as $rawField) {
                        [$field, $mode] = \explode(':', \trim($rawField), 2) + [1 => null];
                        $mode ??= static::SEARCH_MODE_DEFAULT;

                        if (!\in_array($mode, static::SEARCH_MODES, true)) {
                            throw new \ValueError('Invalid mode for data-field [' . $field . ']: ' . $mode . '. Valid modes are: ' . Arr::join(static::SEARCH_MODES, ', ', ' and '));
                        }

                        $parsedDataFields[$field] = $mode;
                    }

                    $dataFields = $parsedDataFields;
                }
            } elseif (!\is_array($dataFields)) {
                throw new \InvalidArgumentException('Attribute data-fields must be of type array|string|true, ' . \get_debug_type($dataFields) . ' given');
            } else {
                $parsedDataFields = [];

                foreach ($dataFields as $dataField => $mode) {
                    if (\is_int($dataField)) {
                        [$dataField, $mode] = \explode(':', $mode, 2) + [1 => static::SEARCH_MODE_DEFAULT];
                    }

                    if (!\in_array($mode, static::SEARCH_MODES, true)) {
                        throw new \ValueError('Invalid mode for data-field [' . $dataField . ']: ' . $mode . '. Valid modes are: ' . Arr::join(static::SEARCH_MODES, ', ', ' and '));
                    }

                    $parsedDataFields[$dataField] = $mode;
                }

                $dataFields = $parsedDataFields;
            }

            // if (\is_string($dataFields) || $dataFields === true) {
            //     $dataFields = $dataFields === true || \strtolower($dataFields) === 'true'
            //         ? true
            //         : \array_map(trim(...), \explode(',', $dataFields));
            //
            //
            //     } else {
            //
            //     }
        } else {
            $dataFields = true;
        }

        $this->dataFields = $dataFields;
        unset($this->componentAttributes['data-fields']);
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
