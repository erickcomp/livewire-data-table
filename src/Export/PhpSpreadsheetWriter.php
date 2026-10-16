<?php

namespace ErickComp\LivewireDataTable\Export;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;

/**
 * Writes with PhpSpreadsheet (phpoffice/phpspreadsheet). The whole spreadsheet is built in memory before it is saved,
 * so the memory used grows with the number of rows: for large exports, OpenSpout is the better choice.
 */
class PhpSpreadsheetWriter implements ExportWriter
{
    protected const WRITER_TYPES = [
        self::FORMAT_CSV => 'Csv',
        self::FORMAT_XLSX => 'Xlsx',
        self::FORMAT_ODS => 'Ods',
    ];

    public function isAvailable(): bool
    {
        return \class_exists(Spreadsheet::class);
    }

    public function supportedFormats(): array
    {
        return \array_keys(static::WRITER_TYPES);
    }

    public function write(string $format, array $header, iterable $rows, string $path): void
    {
        $writerType = static::WRITER_TYPES[$format] ?? throw new \InvalidArgumentException("PhpSpreadsheet cannot write the format [$format]");

        $spreadsheet = new Spreadsheet();

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $this->fillRow($sheet, 1, $header);
            $rowNumber = 2;

            foreach ($rows as $values) {
                $this->fillRow($sheet, $rowNumber++, $values);
            }

            $writer = IOFactory::createWriter($spreadsheet, $writerType);
            $writer->setPreCalculateFormulas(false);

            if ($writer instanceof CsvWriter) {
                // Like OpenSpout's CSV: a UTF-8 BOM (Excel needs it to read accents right), and quotes only when needed
                $writer->setDelimiter((string) \config('erickcomp-livewire-data-table.export.csv-delimiter', ','))
                    ->setUseBOM(true)
                    ->setEnclosureRequired(false)
                    ->setLineEnding("\n");
            }

            $writer->save($path);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * Text is set explicitly as text: PhpSpreadsheet takes text starting with "=" for a formula otherwise
     *
     * @param array<int, string|int|float|null> $values
     */
    protected function fillRow(Worksheet $sheet, int $rowNumber, array $values): void
    {
        foreach (\array_values($values) as $index => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex($index + 1) . $rowNumber,
                $value,
                \is_string($value) ? DataType::TYPE_STRING : DataType::TYPE_NUMERIC,
            );
        }
    }
}
