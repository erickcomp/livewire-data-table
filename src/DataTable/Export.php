<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Concerns\CreatesFromComponentAttributeBag;
use ErickComp\LivewireDataTable\Export\ExportWriter;
use ErickComp\LivewireDataTable\Export\ExportWriterResolver;
use Illuminate\Support\Arr;
use Illuminate\View\ComponentAttributeBag;

/**
 * The export of a data table (<x-data-table.export>): a button, or a dropdown with an entry per format, that downloads
 * the rows matching the search and the filters (all the pages), or only the selected rows when there are any.
 *
 * Its attributes go to the button, except "formats", "filename", "label", "selected-label" and "can".
 */
class Export
{
    use CreatesFromComponentAttributeBag;

    public const DEFAULT_FORMATS = [ExportWriter::FORMAT_CSV, ExportWriter::FORMAT_XLSX];
    public const DEFAULT_FILENAME = 'export-{date}';

    public const CONTENT_TYPES = [
        ExportWriter::FORMAT_CSV => 'text/csv; charset=UTF-8',
        ExportWriter::FORMAT_XLSX => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ExportWriter::FORMAT_ODS => 'application/vnd.oasis.opendocument.spreadsheet',
    ];

    /** @var string[] The formats offered, in the order they were given */
    public array $formats;

    /** The name of the downloaded file, without the extension: "{date}" and "{datetime}" become the current ones */
    public string $filename;

    public ?string $label;

    /** The label while rows are selected (only they are exported then) */
    public ?string $selectedLabel;

    /** Gate ability the user must have to export */
    public ?string $can;

    /**
     * @param string|string[]|null $formats
     *
     * @throws \LogicException when no spreadsheet library is installed (see ExportWriterResolver)
     */
    public function __construct(
        array|string|null $formats = null,
        ?string $filename = null,
        ?string $label = null,
        ?string $selectedLabel = null,
        ?string $can = null,
    ) {
        // Replaced by the attributes left over when made from an <x-data-table.export>: they go to its button
        $this->attributes = new ComponentAttributeBag();

        $formats = \is_string($formats) ? \explode(',', $formats) : ($formats ?? []);
        $formats = \array_values(\array_unique(\array_filter(\array_map(fn($format) => \strtolower(\trim((string) $format)), $formats))));
        $this->formats = empty($formats) ? static::DEFAULT_FORMATS : $formats;

        $writer = \app(ExportWriterResolver::class)->resolve();
        $available = \array_values(\array_intersect(\array_keys(static::CONTENT_TYPES), $writer->supportedFormats()));
        $unsupported = \array_diff($this->formats, $available);

        if (!empty($unsupported)) {
            throw new \InvalidArgumentException(
                'Invalid export format: [' . \reset($unsupported) . ']. The ones the export writer [' . $writer::class . '] supports are: '
                . Arr::join($available, ', ', ' and '),
            );
        }

        $this->filename = \trim($filename ?? '') !== '' ? \trim($filename) : static::DEFAULT_FILENAME;

        if (\preg_match('/[\/\\\\\x00-\x1F\x7F]/', $this->filename)) {
            throw new \InvalidArgumentException("Invalid export filename: [{$this->filename}]. It cannot have slashes or control characters");
        }

        $this->label = \trim($label ?? '') !== '' ? \trim($label) : null;
        $this->selectedLabel = \trim($selectedLabel ?? '') !== '' ? \trim($selectedLabel) : null;
        $this->can = \trim($can ?? '') !== '' ? \trim($can) : null;
    }

    public function hasFormat(string $format): bool
    {
        return \in_array($format, $this->formats, true);
    }

    /**
     * The name of the downloaded file in the given format, with the date placeholders filled
     */
    public function filename(string $format): string
    {
        $now = \now();

        return \strtr($this->filename, [
            '{date}' => $now->format('Y-m-d'),
            '{datetime}' => $now->format('Y-m-d_H-i-s'),
        ]) . '.' . $format;
    }

    public function contentType(string $format): string
    {
        return static::CONTENT_TYPES[$format];
    }

    public function label(): string
    {
        return $this->label ?? __('erickcomp_lw_data_table::messages.export_label');
    }

    public function selectedLabel(): string
    {
        return $this->selectedLabel ?? __('erickcomp_lw_data_table::messages.export_selected_label');
    }

    public function formatLabel(string $format): string
    {
        return __("erickcomp_lw_data_table::messages.export_format_$format");
    }
}
