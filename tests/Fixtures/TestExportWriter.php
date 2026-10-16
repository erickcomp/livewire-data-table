<?php

namespace Tests\Fixtures;

use ErickComp\LivewireDataTable\Export\ExportWriter;

/**
 * An export writer of the application's own: it writes the rows as JSON lines
 */
class TestExportWriter implements ExportWriter
{
    /** PHP's max_execution_time while the last file was written */
    public static string|false|null $maxExecutionTimeWhileWriting = null;

    /** Thrown by write(), when set */
    public static ?\Throwable $failure = null;

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
        static::$maxExecutionTimeWhileWriting = \ini_get('max_execution_time');

        if (static::$failure !== null) {
            throw static::$failure;
        }

        $lines = [\json_encode($header)];

        foreach ($rows as $values) {
            $lines[] = \json_encode($values);
        }

        \file_put_contents($path, \implode("\n", $lines));
    }
}
