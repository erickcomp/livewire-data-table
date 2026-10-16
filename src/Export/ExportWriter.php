<?php

namespace ErickComp\LivewireDataTable\Export;

/**
 * Writes a data table's rows to a spreadsheet file, using a spreadsheet library. The values come ready: text, numbers
 * or null (an empty cell). Text must be written as text, never taken for a formula.
 *
 * The writer is picked by ExportWriterResolver, from the "export.writer" config.
 */
interface ExportWriter
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_XLSX = 'xlsx';
    public const FORMAT_ODS = 'ods';

    /**
     * Whether the library behind the writer is installed
     */
    public function isAvailable(): bool;

    /**
     * The formats it writes, among the FORMAT_* constants
     *
     * @return string[]
     */
    public function supportedFormats(): array;

    /**
     * @param string $format One of supportedFormats()
     * @param array<int, string> $header The column titles
     * @param iterable<array<int, string|int|float|null>> $rows The values of each row, in the order of the header
     * @param string $path The file to write, which may already exist (empty)
     */
    public function write(string $format, array $header, iterable $rows, string $path): void;
}
