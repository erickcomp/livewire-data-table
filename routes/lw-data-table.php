<?php

use ErickComp\LivewireDataTable\Export\ExportFiles;
use ErickComp\LivewireDataTable\Http\Controllers\ExportDownloadController;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;

// The download of a finished export. The URL is signed relative to the site's root, so that the scheme and the host
// the server sees (behind a proxy, for instance) do not matter. The middleware must start the session: a download
// only works in the session that made the export.
Route::get(
    \trim((string) \config('erickcomp-livewire-data-table.export.route.prefix', 'lw-data-table'), '/') . '/export/{file}',
    ExportDownloadController::class,
)
    ->middleware([...(array) \config('erickcomp-livewire-data-table.export.route.middleware', ['web']), ValidateSignature::class . ':relative'])
    ->where('file', '[A-Za-z0-9]{40}')
    ->name(ExportFiles::ROUTE_NAME);
