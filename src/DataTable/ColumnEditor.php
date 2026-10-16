<?php

namespace ErickComp\LivewireDataTable\DataTable;

use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;

/**
 * How a column is edited inline (see the "inline-edit" attribute of the table): the input, its options, the validation
 * rules and the row field it edits.
 *
 * Kept in the data table definition, which goes into the file cache: the rules are strings or serializable rule
 * objects, never closures.
 */
class ColumnEditor
{
    public const TEXT = 'text';
    public const NUMBER = 'number';
    public const SELECT = 'select';
    public const DATE = 'date';
    public const DATETIME = 'datetime';
    public const CHECKBOX = 'checkbox';
    public const TEXTAREA = 'textarea';

    public const TYPES = [self::TEXT, self::NUMBER, self::SELECT, self::DATE, self::DATETIME, self::CHECKBOX, self::TEXTAREA];

    /**
     * @param array<string, string> $options The values and labels of a select
     * @param array<int, mixed> $rules The column's validation rules (see validationRules())
     */
    final public function __construct(
        public readonly string $type,
        public readonly string $field,
        public readonly array $options = [],
        public readonly array $rules = [],
    ) {}

    /**
     * Makes the editor of a column from its attributes, or returns null when the column has no "editor"
     *
     * @param string $column How error messages refer to the column, e.g. "Price"
     *
     * @throws \InvalidArgumentException when the attributes do not make an editor
     */
    public static function fromColumnAttributes(string $column, ?string $editor, mixed $options, mixed $rules, ?string $field, ?string $dataField): ?static
    {
        $type = \strtolower(\trim($editor ?? ''));

        if ($type === '') {
            return null;
        }

        if (!\in_array($type, static::TYPES, true)) {
            throw new \InvalidArgumentException("Invalid editor for the column [$column]: [$editor]. The valid ones are: " . \implode(', ', static::TYPES));
        }

        $field = \trim($field ?? '') !== '' ? \trim($field) : \trim($dataField ?? '');

        if ($field === '') {
            throw new \InvalidArgumentException("The column [$column] has an [editor], but no field to edit: give it a [data-field] or an [editor-field]");
        }

        if ($options !== null && !\is_array($options)) {
            throw new \InvalidArgumentException("The editor options of the column [$column] must be an array of values and labels: use [:editor-options=\"['value' => 'Label']\"]");
        }

        $selectOptions = [];

        foreach ($options ?? [] as $value => $label) {
            $selectOptions[(string) $value] = (string) $label;
        }

        if ($type === static::SELECT && empty($selectOptions)) {
            throw new \InvalidArgumentException("The column [$column] has a select editor, but no [editor-options]");
        }

        return new static($type, $field, $selectOptions, static::parseRules($column, $rules));
    }

    /**
     * The value the input starts with, from the row's value
     */
    public function inputValue(mixed $value): string|int|float|bool
    {
        $value = $value instanceof \BackedEnum ? $value->value : $value;

        return match ($this->type) {
            static::CHECKBOX => \is_string($value) ? (bool) \filter_var($value, FILTER_VALIDATE_BOOL) : (bool) $value,
            static::DATE => static::formatDate($value, 'Y-m-d'),
            static::DATETIME => static::formatDate($value, 'Y-m-d\TH:i'),
            static::NUMBER => \is_int($value) || \is_float($value) ? $value : static::toText($value),
            default => static::toText($value),
        };
    }

    /**
     * The value handed to the handler, from what the input sent (already validated): a number, a boolean, text, or
     * null for an empty number, date or select
     */
    public function value(mixed $input): string|int|float|bool|null
    {
        $isEmpty = $input === null || $input === '';

        return match ($this->type) {
            static::CHECKBOX => (bool) \filter_var($input, FILTER_VALIDATE_BOOL),
            static::NUMBER => $isEmpty ? null : 0 + $input,
            static::DATE, static::DATETIME, static::SELECT => $isEmpty ? null : (string) $input,
            default => $isEmpty ? '' : (string) $input,
        };
    }

    /**
     * The rules the value is validated with: the input's type first ("numeric" for a number, the options for a
     * select...), then the column's. In the column's rules given as text, "{id}" becomes the row's id, e.g.
     * "unique:products,name,{id}".
     *
     * @return array<int, mixed>
     */
    public function validationRules(string $rowId): array
    {
        $typeRule = match ($this->type) {
            static::NUMBER => 'numeric',
            static::DATE, static::DATETIME => 'date',
            static::CHECKBOX => 'boolean',
            static::SELECT => Rule::in(\array_map(\strval(...), \array_keys($this->options))),
            default => 'string',
        };

        $rules = \array_map(fn(mixed $rule) => \is_string($rule) ? \str_replace('{id}', $rowId, $rule) : $rule, $this->rules);

        return ['nullable', $typeRule, ...$rules];
    }

    /**
     * The "type" of the input element
     */
    public function htmlInputType(): string
    {
        return match ($this->type) {
            static::DATETIME => 'datetime-local',
            default => $this->type,
        };
    }

    /**
     * @return array<int, mixed>
     */
    protected static function parseRules(string $column, mixed $rules): array
    {
        if ($rules === null || $rules === '') {
            return [];
        }

        $rules = \is_string($rules) ? \explode('|', $rules) : $rules;

        if (!\is_array($rules)) {
            throw new \InvalidArgumentException("The editor rules of the column [$column] must be a string (\"required|max:100\") or an array");
        }

        foreach ($rules as $rule) {
            if ($rule instanceof \Closure || (!\is_string($rule) && !\is_object($rule))) {
                throw new \InvalidArgumentException("Invalid editor rule for the column [$column]: use validation rules as text or rule objects (closures cannot be kept in the data table's cache)");
            }
        }

        return \array_values(\array_filter($rules, fn(mixed $rule) => !\is_string($rule) || \trim($rule) !== ''));
    }

    protected static function formatDate(mixed $value, string $format): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        if (!\is_string($value) || \trim($value) === '') {
            return static::toText($value);
        }

        try {
            return Date::parse($value)->format($format);
        } catch (\Throwable) {
            return $value;
        }
    }

    protected static function toText(mixed $value): string
    {
        return \is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
    }
}
