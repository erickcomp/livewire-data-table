<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\DataTable\BaseDataTableComponent;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\Support\Facades\Blade;

class Filter // extends BaseDataTableComponent
{
    public const TYPE_TEXT = 'text';
    public const TYPE_NUMBER = 'number';
    public const TYPE_NUMBER_RANGE = 'number-range';
    public const TYPE_DATE = 'date';
    public const TYPE_DATE_PICKER = 'date-picker';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_DATETIME_PICKER = 'datetime-picker';
    public const TYPE_SELECT = 'select';
    public const TYPE_SELECT_MULTIPLE = 'select-multiple';

    public const INPUT_TYPES = [
        self::TYPE_TEXT,
        self::TYPE_NUMBER,
        self::TYPE_DATE,
        self::TYPE_DATE_PICKER,
        self::TYPE_DATETIME,
        self::TYPE_DATETIME_PICKER,
        self::TYPE_SELECT,
        self::TYPE_SELECT_MULTIPLE,
    ];

    public const MODE_CONTAINS = 'contains'; // like
    public const MODE_RANGE = 'range'; // between
    public const MODE_EQUALS = 'equals'; // =
    public const MODE_IN = 'IN'; // IN

    public ComponentAttributeBag $attributes;

    public string $name {
        get => $this->attributes['name'];
    }

    public string $label {
        get => $this->attributes['label'] ?? Str::headline($this->attributes['data-field']);
    }

    public string $dataField {
        get => $this->attributes['data-field'];
    }

    public string $inputType {
        get => $this->attributes['input-type'] ?? static::TYPE_TEXT;
    }

    public string $mode {
        get {
            if ($this->attributes->has('mode')) {
                return $this->attributes['mode'];
            }

            return match ($this->inputType) {
                static::TYPE_TEXT => static::MODE_CONTAINS,
                static::TYPE_DATE, static::TYPE_DATE_PICKER, static::TYPE_DATETIME, static::TYPE_DATETIME_PICKER => static::MODE_RANGE,
                static::TYPE_NUMBER => static::MODE_EQUALS,
                static::TYPE_SELECT => static::MODE_EQUALS,
                static::TYPE_SELECT_MULTIPLE => static::MODE_IN
            };
        }
    }

    public ?string $customRendererCode = null;

    public function __construct(ComponentAttributeBag $attributes, ?string $customRendererCode = null)
    {
        $this->validateAttributes($attributes);

        // HTML names defaults to data-fields's name
        $this->attributes = $attributes->merge(['name' => $attributes['data-field']]);
        $this->customRendererCode = $customRendererCode;
    }

    public function htmlInputType(): ?string
    {
        return match ($this->inputType) {
            static::TYPE_TEXT, static::TYPE_DATE, static::TYPE_DATETIME => 'text',
            static::TYPE_DATE_PICKER => 'date',
            static::TYPE_DATETIME_PICKER => 'datetime-local',
            static::TYPE_NUMBER => 'number',
            default => null
        };
    }

    public function buildInputNameAttribute(string $filterUrlParam, ?string $range = null): string
    {
        if (\is_string($range) && empty(\trim($range))) {
            $range = null;
        }

        $inputName = "{$filterUrlParam}[{$this->dataField}][{$this->name}]";

        if ($range === null) {
            return $inputName;
        }

        if (!\in_array(\strtolower($range), ['from', 'to'])) {
            throw new \LogicException("Invalid range: $range. The valid values for the \$range parameter are: \"from\", \"to\"");
        }

        return "{$inputName}[$range]";
    }

    public function dotName(?string $range = null): string
    {
        $dotName = "{$this->dataField}.{$this->name}";

        if ($range === null) {
            return $dotName;
        }

        if (!\in_array(\strtolower($range), ['from', 'to'])) {
            throw new \LogicException("Invalid range: $range. The valid values for the \$range parameter are: \"from\", \"to\"");
        }

        return "$dotName.$range";
    }

    public function buildWireModelAttribute(string $filterProperty, ?string $range = null): string
    {
        return "$filterProperty." . $this->dotName($range);

        // $wireModel = "$filterProperty.{$this->dataField}.{$this->name}";

        // if ($range === null) {
        //     return $wireModel;
        // }

        // if (!\in_array(\strtolower($range), ['from', 'to'])) {
        //     throw new \LogicException("Invalid range: $range. The valid values for the \$range parameter are: \"from\", \"to\"");
        // }

        // return "$wireModel.$range";
    }

    public function buildXModelAttribute(string $filterProperty, ?string $range = null): string
    {
        //
        $wireModel = "dtData()['$filterProperty']['$this->dataField']['$this->name']";

        if ($range === null) {
            return $wireModel;
        }

        if (!\in_array(\strtolower($range), ['from', 'to'])) {
            throw new \LogicException("Invalid range: $range. The valid values for the \$range parameter are: \"from\", \"to\"");
        }

        return "{$wireModel}['$range']";
    }

    public function getCustomRendererCodeWithXModel(string $filterProperty, array $data = []): string
    {
        $renderedCode = Blade::render($this->customRendererCode, $data + ['___dataTableFilter' => $this]);

        $renderedCode = $this->insertAttributeValueIntoHTML(
            $renderedCode,
            'input,select',
            'name',
            $this->buildInputNameAttribute($filterProperty),
            false,
            '[]',
        );

        $renderedCode = $this->insertAttributeValueIntoHTML(
            $renderedCode,
            'input',
            'x-on:keydown.enter',
            'applyFilters()',
            false,
        );

        return $this->insertAttributeValueIntoHTML(
            $renderedCode,
            'input,select',
            'x-model',
            $this->buildXModelAttribute('inputFilters'),
            false,
            '.',
        );
    }

    public function inputAttributes(array|string $except = []): ComponentAttributeBag
    {
        $attrs = $this->attributes->except(\array_merge(['label', 'data-field', 'input-type', 'mode'], Arr::wrap($except)));

        if ($this->inputType === static::TYPE_SELECT_MULTIPLE) {
            return $attrs->merge(['multiple' => true]);
        }

        return $attrs;
    }
    protected function validateAttributes(ComponentAttributeBag $attributes)
    {
        if (!$attributes->has('data-field')) {
            throw new \LogicException('Data table filters must specify a [data-field] attribute');
        }

        if ($attributes->has('input-type') && !\in_array($attributes['input-type'], static::INPUT_TYPES)) {
            $inputTypesAsStr = \implode(', ', static::INPUT_TYPES);

            throw new \InvalidArgumentException("The attribute [input-type] must be one of the following: [$inputTypesAsStr], \"{$this->attributes['input-type']}\" given");
        }
    }

    protected function insertAttributeValueIntoHTML(string $html, string $selector, string $attribute, string $value, bool $force, ?string $notationForMultiple = null): string
    {
        if (!\in_array(\trim($notationForMultiple), [null, '[]', '.'])) {
            throw new \DomainException("Invalid value for the \$notationForMultiple parameter: $notationForMultiple. The valid values are: null, \"[]\", \".\"");
        }

        $dom = \Dom\HTMLDocument::createFromString($html, \LIBXML_HTML_NOIMPLIED);

        $nodes = $dom->querySelectorAll($selector);

        if ($nodes->count() === 1 && ($force || !$nodes->item(0)->hasAttribute($attribute))) {
            $nodes->item(0)->setAttribute($attribute, $value);

            return $dom->saveHtml();
        }

        $i = 0;
        foreach ($nodes as $node) {
            if ($force || !$node->hasAttribute($attribute)) {
                $indexedVal = $notationForMultiple === '.'
                    ? "$value.$i"
                    : "{$value}[$i]";

                $indexedVal = match ($notationForMultiple) {
                    null => $value,
                    '.' => "$value.$i",
                    '[]' => "{$value}[$i]"
                };

                $node->setAttribute($attribute, $indexedVal);

                $i++;
            }
        }

        return $dom->saveHtml();
    }
}
