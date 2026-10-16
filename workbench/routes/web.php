<?php

use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\TestProduct;

Route::redirect('/', '/demo');

// The table's data-src-pagination comes from ?pagination= (length_aware, simple, cursor or none)
Route::get('/demo/{preset?}', function (Request $request, string $preset = 'vanilla') {
    $pagination = DataSourcePaginationType::tryFromIgnoreCase((string) $request->query('pagination', ''))
        ?? DataSourcePaginationType::LengthAware;

    return view('workbench::demo', ['preset' => $preset, 'pagination' => $pagination->value]);
})
    ->where('preset', 'vanilla|bootstrap4v1|tailwind3v1')
    ->name('demo');

// Targets of the demo's row actions: "View" (and a click on a row) and "Discard" (a DELETE form)
Route::get('/demo/products/{product}', fn(TestProduct $product) => "Product page: {$product->name}")
    ->name('demo.products.show');

Route::delete('/demo/products/{product}', function (TestProduct $product) {
    $product->delete();

    return redirect()->route('demo');
})->name('demo.products.destroy');
