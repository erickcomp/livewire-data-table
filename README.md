# Livewire Data Table

A Blade-centric, reactive data table component for Laravel + Livewire. Define your tables using familiar Blade component syntax and get sorting, searching, filtering, and pagination out of the box — all powered by Livewire under the hood.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/erickcomp/livewire-data-table.svg?style=flat-square)](https://packagist.org/packages/erickcomp/livewire-data-table)
[![License](https://img.shields.io/packagist/l/erickcomp/livewire-data-table.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/erickcomp/livewire-data-table.svg?style=flat-square)](composer.json)

## Features

- **Blade-first API** — define tables, columns, search, filters, and footers entirely in Blade templates using `<x-data-table>` and its sub-components
- **Multiple data sources** — Eloquent models, Eloquent/Query builders, collections, arrays, callables, and custom data providers
- **Preset-based theming** — ships with `vanilla`, `bootstrap4v1`, and `tailwind3v1` presets; fully customizable via config
- **Global search** — full-table search with configurable modes (contains, starts_with, ends_with, exact, fulltext)
- **Per-column search** — individual column search inputs with debounce support
- **Filters** — text, number, date, datetime, select, select-multiple, and range filters with collapsible UI
- **Sorting** — clickable column headers with ascending/descending/none toggle cycle
- **Pagination** — length-aware, simple, and cursor pagination with custom views for Bootstrap and Tailwind
- **Per-page selector** — configurable options with `max` and `all` special values
- **Loading overlay** — built-in spinner overlay with Livewire loading states
- **Custom column rendering** — use Blade content inside column definitions for full control
- **Row-level customization** — dynamic `@rowClass`, `@rowStyle`, and `@rowAttributes` directives
- **Custom footer** — arbitrary footer content via `<x-data-table.footer>`
- **Query string sync** — search, filters, sorting, and pagination state reflected in the URL
- **Internationalization** — ships with English and Brazilian Portuguese translations
- **Eloquent customization interfaces** — override search, filtering, sorting, and query building at the model level
- **Auto-discovery** — zero-config service provider registration

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.3` |
| Laravel | `^12.60` or `^13.10` |
| Livewire | `^3.6` or `^4.0` |
| [erickcomp/laravel-raw-blade-components](https://github.com/erickcomp/laravel-raw-blade-components) | `2.0` (installed automatically) |
| [phpgt/dom](https://github.com/PhpGt/Dom) | `^4.1` (installed automatically) |

### Optional

| Package | Purpose |
|---|---|
| [laravie/serialize-queries](https://github.com/laravie/serialize-queries) | Required when using Eloquent/Query builder instances as data sources |

## Installation

```bash
composer require erickcomp/livewire-data-table
```

The package uses Laravel's auto-discovery, so the service provider is registered automatically.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=erickcomp-livewire-data-table-config
```

Publish the translation files:

```bash
php artisan vendor:publish --tag=erickcomp-livewire-data-table-lang
```

> **Note:** This package stores compiled component definitions in `storage/framework/views/`, alongside Laravel's compiled Blade templates. They are automatically cleared by `php artisan view:clear`, which is typically part of deployment scripts.

The configuration file (`config/erickcomp-livewire-data-table.php`) contains:

- **Global defaults** — `columns-search-debounce-ms`, query string parameter names
- **Presets** — full theme definitions for `empty`, `vanilla`, `bootstrap4v1`, and `tailwind3v1`

Each preset defines CSS classes, icons, transitions, loader overlays, pagination views, and asset styles for every table element. Presets support inheritance via the `extends` key.

You can also define custom presets in your application config under the `app.lw_dt_presets` key:

```php
// config/app.php (or a dedicated config file)
'lw_dt_presets' => [
    'my-theme' => [
        'extends' => 'vanilla',
        'table' => [
            'class' => ['my-custom-table-class'],
        ],
        // ... override any preset keys
    ],
],
```

## Usage

### Basic Table with Eloquent Model

```blade
<x-data-table :data-src="App\Models\User::class" preset="tailwind3v1">
    <x-data-table.column title="ID" data-field="id" sortable />
    <x-data-table.column title="Name" data-field="name" sortable searchable />
    <x-data-table.column title="Email" data-field="email" sortable searchable />
    <x-data-table.column title="Created At" data-field="created_at" sortable />
</x-data-table>
```

### Table with Collection Data

```blade
<x-data-table :data-src="$users" preset="bootstrap4v1" per-page="10,25,50">
    <x-data-table.column title="Name" data-field="name" sortable />
    <x-data-table.column title="Role" data-field="role" />
</x-data-table>
```

### Table with Eloquent Builder

> Requires the `laravie/serialize-queries` package.

```blade
<x-data-table :data-src="App\Models\User::where('active', true)" preset="vanilla">
    <x-data-table.column title="Name" data-field="name" sortable searchable />
    <x-data-table.column title="Email" data-field="email" />
</x-data-table>
```

### Table with Callable Data Source

```blade
<x-data-table data-src="App\Services\UserService@getUsers" preset="tailwind3v1">
    <x-data-table.column title="Name" data-field="name" sortable />
</x-data-table>
```

The callable receives an `LwDataRetrievalParams` instance with all search, filter, sort, and pagination parameters:

```php
namespace App\Services;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use App\Models\User;

class UserService
{
    public function getUsers(LwDataRetrievalParams $params)
    {
        $query = User::query();

        // Apply search, filters, sorting and paginate in one call
        return $params->applyAndPaginate($query);
    }
}
```

### Global Search

```blade
<x-data-table :data-src="App\Models\User::class" preset="tailwind3v1">
    <x-data-table.search data-fields="name,email" />

    <x-data-table.column title="Name" data-field="name" sortable />
    <x-data-table.column title="Email" data-field="email" sortable />
</x-data-table>
```

The `data-fields` attribute accepts a comma-separated list of fields to search across. You can also specify search modes per field:

```blade
<x-data-table.search data-fields="name:contains,email:starts_with,bio:fulltext" />
```

Available search modes: `contains` (default), `starts_with`, `ends_with`, `exact`, `fulltext`.

Setting `data-fields` to `true` (or omitting it) searches all non-hidden model columns when using an Eloquent data source. When using a collection-based data source, `data-fields` must be explicitly provided.

### Per-Column Search

Add `searchable` to column definitions to enable per-column search inputs:

```blade
<x-data-table.column title="Name" data-field="name" searchable />
<x-data-table.column title="Email" data-field="email" searchable="starts_with" />
```

The `searchable` attribute accepts `true` (defaults to `contains` mode) or a specific mode string.

### Filters

```blade
<x-data-table :data-src="App\Models\Order::class" preset="tailwind3v1">
    <x-data-table.filters collapsible="true">
        <x-data-table.filter
            data-field="status"
            label="Status"
            input-type="select"
        />

        <x-data-table.filter
            data-field="created_at"
            label="Date Range"
            input-type="date"
            mode="range"
        />

        <x-data-table.filter
            data-field="total"
            label="Amount"
            input-type="number"
            mode="range"
        />

        <x-data-table.filter
            data-field="customer_name"
            label="Customer"
            input-type="text"
            mode="contains"
        />
    </x-data-table.filters>

    <x-data-table.column title="Order #" data-field="id" sortable />
    <x-data-table.column title="Customer" data-field="customer_name" sortable />
    <x-data-table.column title="Total" data-field="total" sortable />
    <x-data-table.column title="Status" data-field="status" sortable />
    <x-data-table.column title="Date" data-field="created_at" sortable />
</x-data-table>
```

#### Filter Input Types

| Type | Description |
|---|---|
| `text` | Text input (default) |
| `number` | Number input |
| `date` | Date text input |
| `date-picker` | Native date picker |
| `datetime` | Datetime text input |
| `datetime-picker` | Native datetime-local picker |
| `select` | Dropdown select |
| `select-multiple` | Multiple-selection dropdown |

#### Filter Modes

| Mode | SQL Equivalent |
|---|---|
| `exact` | `= value` |
| `contains` | `LIKE %value%` |
| `starts_with` | `LIKE value%` |
| `ends_with` | `LIKE %value` |
| `fulltext` | `MATCH ... AGAINST` |
| `range` | `>= from AND <= to` |
| `IN` | `IN (values)` |

You can also specify the mode inline in the `data-field` attribute:

```blade
<x-data-table.filter data-field="name:starts_with" label="Name" />
```

#### Custom Filter Rendering

Wrap content inside `<x-data-table.filter>` to provide a custom template:

```blade
<x-data-table.filter data-field="status" label="Status">
    <select>
        <option value="">All</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>
</x-data-table.filter>
```

The package automatically injects `x-model` and `name` attributes into `<input>` and `<select>` elements within custom filter templates.

### Custom Column Rendering

Wrap content inside `<x-data-table.column>` to define custom cell rendering:

```blade
<x-data-table.column title="Actions" data-field="id">
    <a href="/users/{{ $__row->id }}/edit">Edit</a>
</x-data-table.column>
```

### Row-Level Customization

Use the `@rowClass`, `@rowStyle`, and `@rowAttributes` directives on `<x-data-table>` to dynamically style rows based on row data. The variable `$__row` represents the current row and `$loop` is the standard loop variable:

```blade
<x-data-table
    :data-src="App\Models\User::class"
    preset="tailwind3v1"
    @rowClass(['bg-red-100' => $__row->is_blocked])
    @rowStyle(['opacity: 0.5' => $__row->is_inactive])
    @rowAttributes(['data-user-id' => $__row->id])
>
    <x-data-table.column title="Name" data-field="name" />
</x-data-table>
```

### Footer

```blade
<x-data-table :data-src="$data" preset="vanilla">
    <x-data-table.column title="Product" data-field="product" />
    <x-data-table.column title="Amount" data-field="amount" />

    <x-data-table.footer>
        <tr>
            <td><strong>Total</strong></td>
            <td>{{ $data->sum('amount') }}</td>
        </tr>
    </x-data-table.footer>
</x-data-table>
```

### Custom Assets

Inject custom CSS or JavaScript alongside the table:

```blade
<x-data-table :data-src="$data" preset="vanilla">
    <x-data-table.assets>
        <style>
            .my-custom-style { color: red; }
        </style>
    </x-data-table.assets>

    <x-data-table.column title="Name" data-field="name" />
</x-data-table>
```

### Pagination

The component supports multiple pagination types via the `data-src-pagination` attribute:

| Value | Type |
|---|---|
| `length_aware` | Full pagination with page numbers (default) |
| `simple` | Previous/Next only |
| `cursor` | Cursor-based pagination |
| `none` | No pagination |

```blade
<x-data-table
    :data-src="App\Models\User::class"
    data-src-pagination="simple"
    per-page="10,25,50,all"
>
    ...
</x-data-table>
```

Special `per-page` values:
- `max` — resolves to the `max-per-page` value (default: 1000)
- `all` — shows all records (translatable label)

### Loading Delay

Control when the loading overlay appears using Livewire delay modifiers:

```blade
<x-data-table :data-src="App\Models\User::class" loading-delay-modifier="short">
    ...
</x-data-table>
```

Valid values: `shortest`, `shorter`, `short`, `long`, `longer`, `longest`.

### PHP Memory Limit

For large datasets, you can increase the memory limit per table:

```blade
<x-data-table :data-src="App\Models\LargeTable::class" php-max-memory="512M">
    ...
</x-data-table>
```

## Component Reference

### `<x-data-table>`

| Attribute | Type | Default | Description |
|---|---|---|---|
| `data-src` | `string\|iterable\|Builder\|callable` | `null` | Data source for the table |
| `preset` | `string` | `'empty'` | UI preset name |
| `per-page` | `string\|array` | Model default or `[15]` | Comma-separated per-page options |
| `max-per-page` | `int` | `1000` | Maximum allowed per-page value |
| `data-src-pagination` | `string` | `'length_aware'` | Pagination type |
| `page-name` | `string` | `'page'` | Query string parameter for page |
| `search-name` | `string` | `'search'` | Query string parameter for search |
| `filters-name` | `string` | `'filters'` | Query string parameter for filters |
| `columns-search-name` | `string` | `'cols-search'` | Query string parameter for column search |
| `columns-search-debounce` | `int` | `250` | Debounce in ms for column search |
| `pagination-view` | `string` | Preset default | Custom pagination view |
| `loading-delay-modifier` | `string` | `null` | Livewire loading delay modifier |
| `data-identity-column` | `string` | `'id'` | Row identity column |
| `php-max-memory` | `string` | `null` | PHP memory_limit override |
| `collection-sorting-flags` | `int\|string` | `SORT_NATURAL \| SORT_FLAG_CASE` | Sorting flags for collection data sources |

HTML attributes prefixed with specific namespaces are forwarded to internal elements:

| Prefix | Target Element |
|---|---|
| `container-` | Main wrapper `<div>` |
| `table-wrapper-` | Table wrapper `<div>` |
| `thead-` | `<thead>` |
| `thead-tr-` | Header `<tr>` |
| `thead-search-tr-` | Column search `<tr>` |
| `thead-search-th-` | Column search `<th>` |
| `th-` | Header `<th>` |
| `tbody-` | `<tbody>` |
| `tbody-tr-` | Body `<tr>` |
| _(no prefix)_ | `<table>` |

### `<x-data-table.column>`

| Attribute | Type | Default | Description |
|---|---|---|---|
| `title` | `string` | **required** | Column header text |
| `data-field` | `string` | `null` | Data key to display |
| `sortable` | `bool` | `false` | Enable sorting |
| `searchable` | `bool\|string` | `false` | Enable per-column search (`true` or mode) |

Supports `th-*`, `td-*`, and `th-search-input-*` prefixes for forwarding attributes.

### `<x-data-table.search>`

| Attribute | Type | Default | Description |
|---|---|---|---|
| `data-fields` | `string\|array\|true` | `true` | Fields to search (comma-separated, or `true` for all) |

Supports `input-*` and `button-*` prefixes for forwarding attributes.

### `<x-data-table.filter>`

| Attribute | Type | Default | Description |
|---|---|---|---|
| `data-field` | `string` | **required** | Column to filter on |
| `label` | `string` | Auto-generated from data-field | Display label |
| `name` | `string` | From `data-field` | HTML input name |
| `input-type` | `string` | `'text'` | Input type (see table above) |
| `mode` | `string` | Auto from input-type | Filter mode (see table above) |

Supports `from-*` and `to-*` prefixes for range filter input attributes.

### `<x-data-table.filters>`

| Attribute | Type | Default | Description |
|---|---|---|---|
| `collapsible` | `bool` | Preset default | Whether the filter container is collapsible |
| `row-length` | `int` | `4` | Number of filter items per row |

Supports `button-toggle-*`, `button-apply-*`, and `filter-item-*` prefixes for forwarding attributes.

## Eloquent Customization Interfaces

Implement these interfaces on your Eloquent models to customize how the data table interacts with your data:

### `CustomizesDataTableQuery`

Provide a custom base query (e.g., eager-load relationships):

```php
use ErickComp\LivewireDataTable\Data\Eloquent\CustomizesDataTableQuery;

class User extends Model implements CustomizesDataTableQuery
{
    public function dataTableQuery(): EloquentBuilder
    {
        return $this->newQuery()->with(['roles', 'department']);
    }
}
```

### `CustomizesDataTableResults`

Take full control of data retrieval:

```php
use ErickComp\LivewireDataTable\Data\Eloquent\CustomizesDataTableResults;

class User extends Model implements CustomizesDataTableResults
{
    public function dataTableData(
        EloquentBuilder $query,
        LwDataRetrievalParams $params,
    ): LengthAwarePaginator {
        return $query->paginate($params->perPage);
    }
}
```

### `CustomizesDataTableSearch`

Override global search behavior:

```php
use ErickComp\LivewireDataTable\Data\Eloquent\CustomizesDataTableSearch;

class User extends Model implements CustomizesDataTableSearch
{
    public function applyDataTableSearch(EloquentBuilder $query, LwDataRetrievalParams $params)
    {
        $query->where(function ($q) use ($params) {
            $q->whereLike('first_name', "%{$params->search}%")
              ->orWhereLike('last_name', "%{$params->search}%");
        });
    }
}
```

### `CustomizesDataTableColumnsSearch`

Override per-column search behavior:

```php
use ErickComp\LivewireDataTable\Data\Eloquent\CustomizesDataTableColumnsSearch;

class User extends Model implements CustomizesDataTableColumnsSearch
{
    public function applyDataTableColumnsSearch(EloquentBuilder $query, LwDataRetrievalParams $params)
    {
        foreach ($params->columnsSearch as $field => $value) {
            $query->whereLike($field, "%{$value}%");
        }
    }
}
```

### `CustomizesDataTableSorting`

Override sorting behavior:

```php
use ErickComp\LivewireDataTable\Data\Eloquent\CustomizesDataTableSorting;

class User extends Model implements CustomizesDataTableSorting
{
    public function applyDataTableSorting(EloquentBuilder $query, LwDataRetrievalParams $params)
    {
        if ($params->sortBy === 'full_name') {
            $query->orderBy('first_name', $params->sortDir)
                  ->orderBy('last_name', $params->sortDir);
            return;
        }

        $query->orderBy($params->sortBy, $params->sortDir);
    }
}
```

## `LwDataRetrievalParams` API

When using a callable data source, the `LwDataRetrievalParams` object provides convenience methods:

| Method | Description |
|---|---|
| `apply($data)` | Apply search, filters, and sorting to a query or collection |
| `applyAndPaginate($data)` | Apply all params and return a `LengthAwarePaginator` |
| `applyAndSimplePaginate($data)` | Apply all params and return a `Paginator` |
| `applyAndCursorPaginate($data)` | Apply all params and return a `CursorPaginator` |
| `paginate($data)` | Paginate without applying search/sort/filter |
| `simplePaginate($data)` | Simple paginate without applying params |
| `cursorPaginate($data)` | Cursor paginate without applying params |

### Available Properties

| Property | Type | Description |
|---|---|---|
| `$params->page` | `?int` | Current page number |
| `$params->perPage` | `?string` | Items per page |
| `$params->pageName` | `string` | Query string parameter name for page |
| `$params->search` | `?string` | Global search term |
| `$params->columnsSearch` | `?array` | Per-column search values (`['field' => 'value']`) |
| `$params->filters` | `?array` | Applied filters |
| `$params->sortBy` | `?string` | Sort column |
| `$params->sortDir` | `?string` | Sort direction (`ASC` or `DESC`) |

## Presets

Presets define the complete visual appearance of the data table. The package ships with:

| Preset | Description |
|---|---|
| `empty` | Bare minimum — no styles, serves as a base for other presets |
| `vanilla` | Minimal CSS with `lw-dt-*` class names and built-in styles |
| `bootstrap4v1` | Bootstrap 4 compatible classes and components |
| `tailwind3v1` | Tailwind CSS 3 utility classes with dark mode support |

Presets support single inheritance via the `extends` key, allowing you to override only the parts you need.

### Preset Resolution

Presets are resolved from two config locations (first match wins):

1. `config('app.lw_dt_presets.<name>')`
2. `config('erickcomp-livewire-data-table.presets.<name>')`

### Preset Structure

Each preset can configure:

- `main-container` — wrapper element classes
- `actions` — search, filters, per-page, and bulk actions container
- `search` — search input and button styling (with icon configuration)
- `filters` — filter container, toggle button, apply button, individual filter items, and input styling per type
- `applied-filters` — active filter badges and removal buttons
- `table` — wrapper, thead, tbody, tfoot, th sorting indicators
- `loader-overlay` — loading spinner template and CSS
- `pagination` — views, container classes, per-page defaults
- `reload-alert` — behavior when the serialized component cache expires
- `assets` — CSS `<style>` blocks injected with the table
- `scripts` — additional JavaScript

## Internationalization

The package includes translation files for:

- English (`en`)
- Brazilian Portuguese (`pt_BR`)

Translation keys cover all UI labels: search button, filter labels, pagination text ("Showing X to Y of Z results"), "No data found" messages, per-page labels, and more.

To add a new locale, publish the translations and create a file at `lang/vendor/erickcomp_lw_data_table/{locale}/messages.php`.

## Testing

The package uses [Pest](https://pestphp.com/) with [Orchestra Testbench](https://github.com/orchestral/testbench):

```bash
composer test
```

Static analysis with [PHPStan](https://phpstan.org/) (level 5):

```bash
composer analyse
```

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Write tests for your changes
4. Run the test suite (`./vendor/bin/pest`)
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).

## Credits

- [Erick de Azevedo Lima](https://github.com/erickcomp)
- [All Contributors](../../contributors)

Built with [Laravel](https://laravel.com) and [Livewire](https://livewire.laravel.com).
