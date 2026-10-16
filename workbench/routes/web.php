<?php

use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/demo');

// The table's data-src-pagination comes from ?pagination= (length_aware, simple, cursor or none)
Route::get('/demo/{preset?}', function (Request $request, string $preset = 'vanilla') {
    $pagination = DataSourcePaginationType::tryFromIgnoreCase((string) $request->query('pagination', ''))
        ?? DataSourcePaginationType::LengthAware;

    return view('workbench::demo', ['preset' => $preset, 'pagination' => $pagination->value]);
})
    ->where('preset', 'vanilla|bootstrap4v1|tailwind3v1')
    ->name('demo');
