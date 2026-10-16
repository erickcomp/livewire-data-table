<?php

namespace ErickComp\LivewireDataTable\Export;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\ODS\Writer as OdsWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Writes with OpenSpout (openspout/openspout, versions 4 and 5): each row goes to the file as it comes, so the memory
 * used does not grow with the number of rows.
 */
class OpenSpoutWriter implements ExportWriter
{
    public function isAvailable(): bool
    {
        return \class_exists(XlsxWriter::class);
    }

    public function supportedFormats(): array
    {
        return [static::FORMAT_CSV, static::FORMAT_XLSX, static::FORMAT_ODS];
    }

    public function write(string $format, array $header, iterable $rows, string $path): void
    {
        $writer = $this->makeWriter($format);
        $writer->openToFile($path);

        try {
            $writer->addRow($this->makeRow($header));

            foreach ($rows as $values) {
                $writer->addRow($this->makeRow($values));
            }
        } finally {
            $writer->close();
        }
    }

    protected function makeWriter(string $format): WriterInterface
    {
        return match ($format) {
            static::FORMAT_CSV => new CsvWriter($this->csvOptions()),
            static::FORMAT_XLSX => new XlsxWriter(),
            static::FORMAT_ODS => new OdsWriter(),
            default => throw new \InvalidArgumentException("OpenSpout cannot write the format [$format]"),
        };
    }

    protected function csvOptions(): CsvOptions
    {
        $delimiter = (string) \config('erickcomp-livewire-data-table.export.csv-delimiter', ',');

        // OpenSpout 5 takes the options in its constructor; OpenSpout 4 has none, and writable properties instead
        if ((new \ReflectionClass(CsvOptions::class))->getConstructor() !== null) {
            return new CsvOptions(FIELD_DELIMITER: $delimiter);
        }

        $options = new CsvOptions();
        (new \ReflectionProperty(CsvOptions::class, 'FIELD_DELIMITER'))->setValue($options, $delimiter);

        return $options;
    }

    /**
     * Text always goes in a text cell: OpenSpout takes text starting with "=" for a formula otherwise
     *
     * @param array<int, string|int|float|null> $values
     */
    protected function makeRow(array $values): Row
    {
        $cells = [];

        foreach (\array_values($values) as $value) {
            $cells[] = \is_string($value) && $value !== '' ? new StringCell($value, null) : Cell::fromValue($value);
        }

        return new Row($cells);
    }
}
