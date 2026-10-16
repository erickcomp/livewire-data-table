<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Livewire Data Table demo ({{ $preset }})</title>

    @if ($preset === 'bootstrap4v1')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    @elseif ($preset === 'tailwind3v1')
        <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    @endif

    <style>
        .demo-nav { display: flex; gap: 1rem; padding: 1rem; font-family: sans-serif; }
        .demo-nav a.active { font-weight: bold; }
    </style>

    @livewireStyles
</head>
<body>
    <nav class="demo-nav">
        @foreach (['vanilla', 'bootstrap4v1', 'tailwind3v1'] as $demoPreset)
            <a href="{{ route('demo', ['preset' => $demoPreset, 'pagination' => $pagination]) }}" @class(['active' => $demoPreset === $preset])>{{ $demoPreset }}</a>
        @endforeach

        <span>|</span>

        @foreach (\ErickComp\LivewireDataTable\Data\DataSourcePaginationType::values() as $demoPagination)
            <a href="{{ route('demo', ['preset' => $preset, 'pagination' => $demoPagination]) }}" @class(['active' => $demoPagination === $pagination])>{{ $demoPagination }}</a>
        @endforeach
    </nav>

    <main style="padding: 1rem;">
        <x-data-table :data-src="Tests\Fixtures\TestProduct::class" :preset="$preset" :data-src-pagination="$pagination" per-page="15,30,60" id="products" selectable>
            <x-data-table.search data-fields="name,category" />

            <x-data-table.filters>
                <x-data-table.filter
                    data-field="category"
                    label="Category"
                    input-type="select"
                    :options="['' => '', 'Books' => 'Books', 'Electronics' => 'Electronics', 'Garden' => 'Garden', 'Toys' => 'Toys']"
                />
                <x-data-table.filter data-field="price" label="Price" input-type="number" mode="range" />
            </x-data-table.filters>

            <x-data-table.column title="ID" data-field="id" sortable />
            <x-data-table.column title="Name" data-field="name" sortable searchable />
            <x-data-table.column title="Category" data-field="category" sortable searchable />
            <x-data-table.column title="Price" data-field="price" sortable />
        </x-data-table>
    </main>

    @livewireScripts
</body>
</html>
