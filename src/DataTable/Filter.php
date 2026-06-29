<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Concerns\WorksWithTextSearchingAndFiltering;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\Support\Facades\Blade;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;

/**
 * @property-read string $name
 * @property-read string $label
 * @property-read string $dataField
 * @property-read string $inputType
 * @property-read string $mode
 */
class Filter
{
    use FillsComponentAttributeBags;
    use WorksWithTextSearchingAndFiltering;

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
        self::TYPE_NUMBER_RANGE,
        self::TYPE_DATE,
        self::TYPE_DATE_PICKER,
        self::TYPE_DATETIME,
        self::TYPE_DATETIME_PICKER,
        self::TYPE_SELECT,
        self::TYPE_SELECT_MULTIPLE,
    ];

    public const MODE_EXACT = self::TEXT_MODE_EXACT; // = <value>
    public const MODE_CONTAINS = self::TEXT_MODE_CONTAINS; // like %<value>%
    public const MODE_STARTS_WITH = self::TEXT_MODE_STARTS_WITH; // like <value>%
    public const MODE_ENDS_WITH = self::TEXT_MODE_ENDS_WITH; // like %<value>
    public const MODE_FULLTEXT = self::TEXT_MODE_FULLTEXT; // like %<value>
    public const MODE_RANGE = 'range'; // between
    //public const MODE_EQUALS = 'equals'; // =
    public const MODE_IN = 'IN'; // IN

    public const MODES = [
        self::MODE_EXACT,
        self::MODE_CONTAINS,
        self::MODE_STARTS_WITH,
        self::MODE_ENDS_WITH,
        self::MODE_FULLTEXT,
        self::MODE_RANGE,
        self::MODE_IN,
    ];

    public ComponentAttributeBag $attributes;
    public ComponentAttributeBag $rangeFromAttributes;
    public ComponentAttributeBag $rangeToAttributes;

    // public string $name {
    //     get => $this->attributes['name'];
    // }

    // public string $label {
    //     get => $this->attributes['label'] ?? Str::headline($this->attributes['data-field']);
    // }

    // public string $dataField {
    //     get => $this->attributes['data-field'];
    // }

    // public string $inputType {
    //     get => $this->attributes['input-type'] ?? static::TYPE_TEXT;
    // }

    // public string $mode {
    //     get {
    //         if ($this->attributes->has('mode')) {
    //             return $this->attributes['mode'];
    //         }

    //         return match ($this->inputType) {
    //             static::TYPE_TEXT => static::MODE_CONTAINS,
    //             static::TYPE_DATE, static::TYPE_DATE_PICKER, static::TYPE_DATETIME, static::TYPE_DATETIME_PICKER => static::MODE_RANGE,
    //             static::TYPE_NUMBER => static::MODE_EXACT,
    //             static::TYPE_SELECT => static::MODE_EXACT,
    //             static::TYPE_SELECT_MULTIPLE => static::MODE_IN
    //         };
    //     }
    // }

    public ?string $customRendererCode = null;

    // O HTML final do custom renderer é cacheado aqui após o primeiro render.
    // Isso permite que processFilters() (que roda ANTES da view) renderize o Blade uma única vez,
    // extraia as options do <select> para montar os labels dos filtros aplicados,
    // e depois a view reutilize o HTML já pronto sem re-renderizar.
    protected ?string $cachedRenderedCustomCode = null;

    public function __construct(ComponentAttributeBag $attributes, ?string $customRendererCode = null)
    {
        $this->validateAttributes($attributes);



        $this->fillComponentAttributeBags($attributes);

        $this->customRendererCode = $customRendererCode;
    }

    public function getSelectOptions(): array
    {
        $options = $this->attributes->get('options', []);

        return \is_array($options) ? $options : [];
    }

    /**
     * Extrai as options de elementos <select> presentes no HTML renderizado do custom renderer
     * e popula o atributo 'options' para que getSelectOptions() funcione corretamente.
     *
     * Necessário porque quando um filtro select usa custom renderer (slot), a prop 'options'
     * não é passada, e sem ela os labels dos filtros aplicados mostram o value bruto
     * em vez do texto legível (ex: "1" em vez de "Sim").
     *
     * @throws \LogicException Se o HTML do custom renderer não contiver um <select>, ou se
     *                         não houver <option> com value não vazio e a prop 'options'
     *                         não tiver sido passada explicitamente.
     */
    protected function extractSelectOptionsFromRenderedHtml(string $renderedHtml): void
    {
        if (!\in_array($this->inputType, [self::TYPE_SELECT, self::TYPE_SELECT_MULTIPLE], true)) {
            return;
        }

        $wrapperId = '__dt_opts_' . \bin2hex(\random_bytes(4));
        $dom = new \Gt\Dom\HTMLDocument("<div id=\"{$wrapperId}\">{$renderedHtml}</div>");
        $wrapper = $dom->getElementById($wrapperId);

        // Se declarou input-type="select" com custom renderer, o HTML deve conter um <select>.
        // Sem ele, o filtro não funciona — melhor falhar cedo com mensagem clara.
        if ($wrapper->querySelectorAll('select')->count() === 0) {
            throw new \LogicException(
                "Data table filter [{$this->name}] has input-type=\"{$this->inputType}\" with a custom renderer, "
                . "but the rendered HTML does not contain a <select> element."
            );
        }

        if (!empty($this->getSelectOptions())) {
            return;
        }

        $options = [];
        foreach ($wrapper->querySelectorAll('option') as $option) {
            $value = $option->getAttribute('value') ?? '';
            $label = \trim($option->textContent);

            if ($value !== '' || $label !== '') {
                $options[$value] = $label;
            }
        }

        // Se não passou a prop 'options' explicitamente, o HTML é a única fonte de options.
        // Sem nenhuma <option> com value, os labels dos filtros aplicados ficariam vazios.
        if (empty($options)) {
            throw new \LogicException(
                "Data table filter [{$this->name}] has input-type=\"{$this->inputType}\" with a custom renderer, "
                . "but no 'options' prop was provided and the rendered HTML contains no <option> elements with a non-empty value."
            );
        }

        $this->attributes['options'] = $options;
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
        if ($this->cachedRenderedCustomCode !== null) {
            return $this->cachedRenderedCustomCode;
        }

        $renderedCode = Blade::render($this->customRendererCode, $data + ['___dataTableFilter' => $this]);

        // Para filtros do tipo select com custom renderer, extrai as options do HTML renderizado
        // e popula o atributo 'options'. Isso é feito aqui (antes das manipulações de DOM abaixo)
        // para aproveitar o HTML já renderizado pelo Blade e evitar um segundo parse.
        // Quando processFilters() chama este método antes da view, as options ficam disponíveis
        // para montar os labels dos filtros aplicados via getSelectOptions().
        $this->extractSelectOptionsFromRenderedHtml($renderedCode);

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

        $this->cachedRenderedCustomCode = $this->insertAttributeValueIntoHTML(
            $renderedCode,
            'input,select',
            'x-model',
            $this->buildXModelAttribute('inputFilters'),
            false,
            '.',
        );

        return $this->cachedRenderedCustomCode;
    }

    public function inputAttributes(array|string $except = [], ?string $range = null): ComponentAttributeBag
    {
        if ($range !== null && !\in_array(\strtolower($range), ['from', 'to'])) {
            throw new \LogicException("Invalid range: $range. The valid values for the \$range parameter are: \"from\", \"to\"");
        }

        $attrs = $this->attributes->except(\array_merge(['label', 'data-field', 'input-type', 'mode', 'options'], Arr::wrap($except)));

        if ($range === 'from') {
            $attrs = $attrs->merge($this->rangeFromAttributes->all());
        } elseif ($range === 'to') {
            $attrs = $attrs->merge($this->rangeToAttributes->all());
        }

        if ($this->inputType === static::TYPE_SELECT_MULTIPLE) {
            return $attrs->merge(['multiple' => true]);
        } elseif ($this->inputType === static::TYPE_DATETIME_PICKER) {
            return $attrs->merge(['step' => '1']);
        }

        return $attrs;
    }

    public function __get(string $property): mixed
    {
        $getMode = function (): string {
            if ($this->attributes->has('mode')) {
                return $this->attributes['mode'];
            }

            return match ($this->inputType) {
                static::TYPE_TEXT => static::MODE_CONTAINS,
                static::TYPE_DATE, static::TYPE_DATE_PICKER, static::TYPE_DATETIME, static::TYPE_DATETIME_PICKER => static::MODE_RANGE,
                static::TYPE_NUMBER => static::MODE_EXACT,
                static::TYPE_NUMBER_RANGE => static::MODE_RANGE,
                static::TYPE_SELECT => static::MODE_EXACT,
                static::TYPE_SELECT_MULTIPLE => static::MODE_IN,
                default => throw new \RuntimeException('Unknown inputType: ' . \var_export($this->inputType, true))
            };
        };

        return match ($property) {
            'name' => $this->attributes['name'],
            'label' => $this->attributes['label'] ?? Str::headline($this->attributes['data-field']),
            'dataField' => $this->attributes['data-field'],
            'inputType' => $this->attributes['input-type'] ?? static::TYPE_TEXT,
            'mode' => $getMode(),
            default => trigger_error("Undefined property: " . static::class . "::$property", \E_USER_WARNING)
        };
    }

    protected function validateAttributes(ComponentAttributeBag $attributes)
    {
        if (!$attributes->has('data-field')) {
            throw new \LogicException('Data table filters must specify a [data-field] attribute');
        }

        $dataField = $attributes->get('data-field');

        if (\str_contains($dataField, ':')) {
            [$dataField, $mode] = \explode(':', $dataField, 2) + [1 => ''];

            if (!empty(\trim($mode))) {
                if ($attributes->has('mode')) {
                    $errmsg = 'You may define the filtering mode by using a suffix into the data-field attribute or by using the mode attribute, but not both at the same time';
                    throw new \LogicException($errmsg);
                }

                $attributes['data-field'] = $dataField;
                $attributes['mode'] = \trim($mode);
            }
        }

        if ($attributes->has('input-type') && !\in_array($attributes['input-type'], static::INPUT_TYPES)) {
            $inputTypesAsStr = \implode(', ', static::INPUT_TYPES);

            throw new \InvalidArgumentException("The attribute [input-type] must be one of the following: [$inputTypesAsStr], \"{$attributes['input-type']}\" given");
        }

        if ($attributes->has('mode') && !\in_array($attributes['mode'], static::MODES, true)) {
            $modesAsStr = \implode(', ', static::MODES);

            throw new \InvalidArgumentException("The attribute [mode] must be one of the following: [$modesAsStr], \"{$attributes['mode']}\" given");
        }

        // HTML names defaults to data-fields's name
        if (!$attributes->has('name')) {
            $attributes['name'] = $this->normalizeNameAttribute($attributes['data-field']);
        }
    }

    protected function normalizeNameAttribute(string $name): string
    {
        $name = \preg_replace('/\s/u', '_', $name);

        return $name;
    }

    protected function insertAttributeValueIntoHTML(string $html, string $selector, string $attribute, string $value, bool $force, ?string $notationForMultiple = null): string
    {
        if (!\in_array(\trim($notationForMultiple), [null, '[]', '.'])) {
            throw new \DomainException("Invalid value for the \$notationForMultiple parameter: $notationForMultiple. The valid values are: null, \"[]\", \".\"");
        }

        $wrapperId = '__dt_wrapper_' . \bin2hex(\random_bytes(4));
        $dom = new \Gt\Dom\HTMLDocument("<div id=\"{$wrapperId}\">{$html}</div>");
        $wrapper = $dom->getElementById($wrapperId);

        $nodes = $wrapper->querySelectorAll($selector);

        if ($nodes->count() === 1 && ($force || !$nodes->item(0)->hasAttribute($attribute))) {
            $nodes->item(0)->setAttribute($attribute, $value);

            return $wrapper->innerHTML;
        }

        $i = 0;
        foreach ($nodes as $node) {
            if ($force || !$node->hasAttribute($attribute)) {
                $indexedVal = match ($notationForMultiple) {
                    null => $value,
                    '.' => "$value.$i",
                    '[]' => "{$value}[$i]"
                };

                $node->setAttribute($attribute, $indexedVal);

                $i++;
            }
        }

        return $wrapper->innerHTML;
    }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'attributes', //default
            'from-' => 'rangeFromAttributes',
            'to-' => 'rangeToAttributes',
        ];
    }
}
