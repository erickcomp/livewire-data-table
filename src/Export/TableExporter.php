<?php

namespace ErickComp\LivewireDataTable\Export;

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\CustomRenderedColumn;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;

/**
 * Turns a data table's rows into the values of an export: one per exported column, as text, a number or null.
 *
 * - A data column exports its value. Dates, enums and other objects become text, as the table shows them.
 * - A custom column exports the row's "data-field", if it has one; otherwise, what it renders, as plain text. Rendering
 *   the Blade of every cell costs much more than reading a field.
 * - Any column with an "export-field" exports that field of the row instead.
 */
class TableExporter
{
    /** The characters that make a spreadsheet take a CSV cell for a formula */
    public const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @param Column[] $columns The exported columns
     * @param bool $escapeFormulas Whether text that a spreadsheet would take for a formula gets a leading "'" (CSV)
     */
    public function __construct(
        protected DataTable $dataTable,
        protected array $columns,
        protected bool $escapeFormulas = false,
    ) {
        $this->columns = \array_values($columns);
    }

    /**
     * @return array<int, string>
     */
    public function header(): array
    {
        return \array_map(fn(Column $column): string => (string) $this->escape($column->title), $this->columns);
    }

    /**
     * @param iterable<mixed> $rows
     * @return \Generator<int, array<int, string|int|float|null>>
     */
    public function rows(iterable $rows): \Generator
    {
        // A custom column's <td> attributes, handed to its Blade as $attributes like when the table renders it
        $tdPresetClass = $this->dataTable->preset()->get('table.tbody.tr.td.class') ?? [];
        $tdAttributes = [];
        $index = 0;

        foreach ($rows as $row) {
            $values = [];

            foreach ($this->columns as $columnIndex => $column) {
                $values[] = $this->escape(match (true) {
                    $column->exportField !== null => static::toCellValue(\data_get($row, $column->exportField)),
                    $column instanceof DataColumn => static::toCellValue($column->getCellData($row, $index + 1)),
                    $column instanceof CustomRenderedColumn && $column->dataField !== null => static::toCellValue(\data_get($row, $column->dataField)),
                    $column instanceof CustomRenderedColumn => $this->renderedText($column, $row, $index, clone ($tdAttributes[$columnIndex] ??= $column->buildTdAttributes($tdPresetClass))),
                    default => null,
                });
            }

            yield $index++ => $values;
        }
    }

    /**
     * A value as a spreadsheet cell holds it: text, a number or null (empty)
     */
    public static function toCellValue(mixed $value): string|int|float|null
    {
        return match (true) {
            $value === null, \is_string($value) => $value,
            \is_int($value), \is_float($value) && \is_finite($value) => $value,
            \is_float($value) => (string) $value,
            \is_bool($value) => (int) $value,
            $value instanceof \BackedEnum => static::toCellValue($value->value),
            $value instanceof \UnitEnum => $value->name,
            $value instanceof \DateTimeInterface => $value instanceof \Stringable ? (string) $value : $value->format('Y-m-d H:i:s'),
            $value instanceof Htmlable => static::htmlToText($value->toHtml()),
            $value instanceof \Stringable => (string) $value,
            $value instanceof Arrayable => static::toJson($value->toArray()),
            default => static::toJson($value),
        };
    }

    /**
     * Prefixes a "'" to text that a spreadsheet would run as a formula when it opens a CSV file. Numbers are left
     * alone, and so is text holding just a number ("-12.5"): a spreadsheet reads it as the number it is.
     */
    public static function escapeFormula(string|int|float|null $value): string|int|float|null
    {
        if (!\is_string($value) || $value === '' || \is_numeric($value) || !\in_array($value[0], static::FORMULA_TRIGGERS, true)) {
            return $value;
        }

        return "'" . $value;
    }

    protected function escape(string|int|float|null $value): string|int|float|null
    {
        return $this->escapeFormulas ? static::escapeFormula($value) : $value;
    }

    /**
     * What a custom column shows for the row, as plain text
     */
    protected function renderedText(CustomRenderedColumn $column, mixed $row, int $index, ComponentAttributeBag $tdAttributes): string
    {
        // As much of Blade's $loop as is known while the rows stream (not how many there are)
        $loop = (object) [
            'index' => $index,
            'iteration' => $index + 1,
            'remaining' => null,
            'count' => null,
            'first' => $index === 0,
            'last' => null,
            'odd' => $index % 2 === 0,
            'even' => $index % 2 === 1,
            'depth' => 1,
            'parent' => null,
        ];

        $html = Blade::render(
            $column->customRendererCode,
            \array_merge($this->dataTable->allViewData(), ['attributes' => $tdAttributes, 'loop' => $loop, '__row' => $row]),
        );

        return static::htmlToText($html);
    }

    /**
     * The text of some HTML, with its whitespace collapsed as a browser shows it
     */
    protected static function htmlToText(string $html): string
    {
        $text = \html_entity_decode(\strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return \trim((string) \preg_replace('/\s+/u', ' ', $text));
    }

    protected static function toJson(mixed $value): string
    {
        $json = \json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        return $json === false ? '' : $json;
    }
}
