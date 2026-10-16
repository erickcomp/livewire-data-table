<?php

namespace ErickComp\LivewireDataTable\Http\Controllers;

use ErickComp\LivewireDataTable\Export\ExportFiles;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Downloads a finished export (see ExportFiles), behind a signed URL
 */
class ExportDownloadController
{
    public function __invoke(Request $request, ExportFiles $files, string $file): Response
    {
        return $files->download($request, $file);
    }
}
