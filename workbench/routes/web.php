<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/demo');

Route::get('/demo/{preset?}', fn(string $preset = 'vanilla') => view('workbench::demo', ['preset' => $preset]))
    ->where('preset', 'vanilla|bootstrap4v1|tailwind3v1')
    ->name('demo');
