<?php

namespace ErickComp\LivewireDataTable\Export;

/**
 * Picks the export writer from the "export.writer" config:
 *
 * - "auto" (the default): OpenSpout when installed, PhpSpreadsheet otherwise;
 * - "openspout" or "phpspreadsheet": that one, which must be installed;
 * - the name of a class implementing ExportWriter.
 *
 * Writers are made by the container, so they can be swapped there.
 */
class ExportWriterResolver
{
    public const AUTO = 'auto';

    /** The bundled writers, in the order "auto" tries them, with the package each one needs */
    public const WRITERS = [
        'openspout' => [OpenSpoutWriter::class, 'openspout/openspout'],
        'phpspreadsheet' => [PhpSpreadsheetWriter::class, 'phpoffice/phpspreadsheet'],
    ];

    /**
     * @throws \LogicException when the writer's library is not installed (or none is, under "auto")
     * @throws \InvalidArgumentException when the config names no writer
     */
    public function resolve(): ExportWriter
    {
        $configured = \trim((string) (\config('erickcomp-livewire-data-table.export.writer') ?? static::AUTO));
        $name = \strtolower($configured);

        if ($name === static::AUTO || $name === '') {
            return $this->firstAvailableWriter();
        }

        if (isset(static::WRITERS[$name])) {
            [$class, $package] = static::WRITERS[$name];
            $writer = $this->makeWriter($class);

            if (!$writer->isAvailable()) {
                throw new \LogicException("The data table exports are set to use the writer [$name] (config \"erickcomp-livewire-data-table.export.writer\"): please install the package $package");
            }

            return $writer;
        }

        if (!\is_a($configured, ExportWriter::class, true)) {
            throw new \InvalidArgumentException('Invalid export writer: [' . $configured . '] (config "erickcomp-livewire-data-table.export.writer"). Use auto, openspout, phpspreadsheet or the name of a class implementing [' . ExportWriter::class . ']');
        }

        return $this->makeWriter($configured);
    }

    protected function firstAvailableWriter(): ExportWriter
    {
        foreach (static::WRITERS as [$class]) {
            $writer = $this->makeWriter($class);

            if ($writer->isAvailable()) {
                return $writer;
            }
        }

        throw new \LogicException('To export a data table, please install one of the packages: openspout/openspout (recommended: it writes the rows as they come, with constant memory) or phpoffice/phpspreadsheet');
    }

    /**
     * @param class-string<ExportWriter> $class
     */
    protected function makeWriter(string $class): ExportWriter
    {
        return \app()->make($class);
    }
}
