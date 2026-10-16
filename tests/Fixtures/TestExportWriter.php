<?php

namespace Tests\Fixtures;

use ErickComp\LivewireDataTable\Export\ExportWriter;

/**
 * An export writer of the application's own: it writes the rows as JSON lines
 */
class TestExportWriter implements ExportWriter
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function supportedFormats(): array
    {
        return [static::FORMAT_CSV];
    }

    public function write(string $format, array $header, iterable $rows, string $path): void
    {
        $lines = [\json_encode($header)];

        foreach ($rows as $values) {
            $lines[] = \json_encode($values);
        }

        \file_put_contents($path, \implode("\n", $lines));
    }
}
