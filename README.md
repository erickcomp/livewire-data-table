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
- **Row selection** — a checkbox per row, a page checkbox, a "select all matching rows" menu and a records counter, à la Magento's admin grid
- **Bulk actions** — an "Actions" dropdown that runs PHP handlers (or dispatches Livewire events) on the selected rows, with sub-actions, confirmation, gate checks and result messages
- **Row actions** — a column of per-row actions (links, routes, forms with any HTTP method, PHP handlers or events) shown side by side or in a dropdown, with per-row gate checks, and rows that react to clicks
- **Inline editing** — edit a row in place (text, number, select, date, datetime, checkbox and textarea inputs), validated with Laravel's rules and saved by a PHP handler
- **Loading overlay** — built-in spinner overlay with Livewire loading states
- **Custom column rendering** — use Blade content inside column definitions for full control
- **Row-level customization** — dynamic `@rowClass`, `@rowStyle`, and `@rowAttributes` directives
- **Custom footer** — arbitrary footer content via `<x-data-table.footer>`
- **Query string sync** — search, filters, sorting, rows per page and page reflected in the URL, so a bookmark brings a view back; optionally, the last state is remembered in the session
- **Clear all and refresh** — a button to drop the search and filters at once, and an `lw-dt-refresh` event to re-render a table from anywhere on the page
- **Export** — download the matching rows (from every page) or the selected ones as CSV, XLSX or ODS, written by OpenSpout or PhpSpreadsheet
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
| [phpgt/dom](https://github.com/PhpGt/Dom) | `^4.2` (installed automatically) |

> **Note:** besides requiring phpgt/dom `^4.2`, the package declares `"conflict": {"phpgt/cssxpath": "<1.1.2 || >=1.6.0"}`:
>
> - **phpgt/dom `^4.2`**: the package references phpgt/dom classes as `GT\Dom\…`, the namespace declared from 4.2.0 on.
> - **phpgt/cssxpath `>=1.6.0`**: 1.6.0 declares its classes as `GT\CssXPath\…`, but phpgt/dom (up to 4.2.2) still imports `Gt\CssXPath\Translator`. PHP class names are case-insensitive and the PSR-4 autoloader still finds the file, but Composer's classmap lookup is case-sensitive, so in applications whose autoloader is dumped with `--classmap-authoritative`, rendering a filter with a custom renderer fails with `Class "Gt\CssXPath\Translator" not found`. This upper bound will be lifted once a phpgt/dom release imports `GT\CssXPath`.
> - **phpgt/cssxpath `<1.1.2`**: 1.1.0 and 1.1.1 use `continue` inside a `switch`, which PHP reports as a compile warning (`"continue" targeting switch is equivalent to "break"`) that Laravel turns into an exception.

### Optional

| Package | Purpose |
|---|---|
| [laravie/serialize-queries](https://github.com/laravie/serialize-queries) | Required when using Eloquent/Query builder instances as data sources |
| [openspout/openspout](https://github.com/openspout/openspout) (`^4.0` or `^5.0`) | Writes the [exports](#export), row by row (recommended) |
| [phpoffice/phpspreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) | Writes the [exports](#export) when OpenSpout is not installed |

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

- **Global defaults** — `columns-search-debounce-ms`, query string parameter names (`query-string-*`), `max-selected-ids`
- **Exports** — `export.writer`, `export.escape-formulas`, `export.csv-delimiter`, `export.max-rows`, and where the files wait for their download: `export.disk`, `export.directory`, `export.download-ttl` and `export.route` (see [Export](#export))
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

Attributes prefixed with `th-search-input-` go to the column's search input, so Alpine plugins work there:

```blade
<x-data-table.column title="CPF" data-field="cpf" searchable th-search-input-x-mask="999.999.999-99" />
```

The search input keeps its value in local Alpine state and sends it to the server only on real input,
debounced by the data table's `columns-search-debounce`. A plugin that writes to the model while the input
mounts (like `x-mask`) doesn't fire a request when the page opens.

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

The search and the filters in use show up above the table as chips, each with a button to remove it, followed by a **Clear all** button that removes them all at once (the column searches are left as they are).

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

### Custom Scripts

Inject JavaScript that runs once per component instance, using Livewire's `@script` mechanism:

```blade
<x-data-table :data-src="$data" preset="vanilla">
    <x-data-table.scripts>
        <script>
            console.log('Table mounted/updated');
        </script>
    </x-data-table.scripts>

    <x-data-table.column title="Name" data-field="name" />
</x-data-table>
```

**Assets vs. Scripts:**
- `<x-data-table.assets>` is injected via Livewire's `@assets` directive — deduplicated **page-wide by content**. Use it for CSS `<style>` blocks or one-time global setup.
- `<x-data-table.scripts>` is injected via Livewire's `@script` directive — scoped **per component instance**, safely re-invoked across Livewire updates without duplicate execution. Use it for JavaScript tied to this specific table instance.

### Passing Data to Renderers

Custom column renderers, custom filter renderers, footer templates, and custom search templates run inside isolated `Blade::render()` scopes — they don't inherit variables from the parent view. To pass data into these renderers, use **dynamic view data** and/or **static view data**.

#### Dynamic View Data (`dynamic-view-data`)

Data that **varies per user or per request** — permissions, session state, locale, user preferences.

**How it works:** Serialized and encrypted into a Livewire property on mount. Travels over the wire on every Livewire update (sort, filter, paginate). Decrypted and unserialized on the server for each render cycle.

**When to use:** The data is different for each user viewing the same page. Storing it in the compiled cache would create a separate cache file per user — wasteful. Instead, it rides along with the Livewire snapshot.

**Keep it small.** Every byte in dynamic view data is sent on every interaction.

```blade
<x-data-table
    :data-src="App\Models\Order::class"
    preset="tailwind3v1"
    :dynamic-view-data="[
        'permissions' => $userPermissions,
        'locale' => app()->getLocale(),
    ]"
>
    <x-data-table.column title="Actions" data-field="id">
        @if($permissions->canEdit)
            <a href="/orders/{{ $__row->id }}/edit">Edit</a>
        @endif
    </x-data-table.column>
</x-data-table>
```

#### Static View Data (`static-view-data`)

Data that is **the same for all users** — ViewModels, formatters, configuration objects, app settings.

**How it works:** Serialized with the compiled DataTable into `storage/framework/views/`. Loaded from disk on every Livewire hydration. Never sent over the wire — zero network overhead.

**When to use:** The data doesn't change between users or requests. A ViewModel with convenience methods, a currency formatter, app config values — these are identical for every visitor. Compiling them into the cache file means they're loaded from disk (fast) instead of transmitted over the network (slow for large objects).

**Trade-off:** Different static view data values produce different cache files. This is fine for shared objects (one file serves all users), but would cause cache proliferation if the values varied per user.

```blade
@php
    $vm = new App\ViewModels\OrderTableViewModel();
@endphp

<x-data-table
    :data-src="App\Models\Order::class"
    preset="tailwind3v1"
    :static-view-data="[
        'vm' => $vm,
        'currency' => $currency,
        'appName' => config('app.name'),
    ]"
>
    <x-data-table.column title="Status" data-field="status">
        <span class="{{ $vm->statusClass($__row->status) }}">
            {{ $vm->statusLabel($__row->status) }}
        </span>
    </x-data-table.column>

    <x-data-table.column title="Total" data-field="total">
        {{ $currency->format($__row->total) }}
    </x-data-table.column>

    <x-data-table.footer>
        <tr>
            <td colspan="2">{{ $appName }} — all values in {{ $currency->code }}</td>
        </tr>
    </x-data-table.footer>
</x-data-table>
```

#### Combining Both

Use both attributes together when your table needs shared configuration and per-user context. When keys overlap, dynamic view data takes precedence over static:

```blade
<x-data-table
    :data-src="App\Models\Order::class"
    :static-view-data="['vm' => $vm, 'currency' => $currency]"
    :dynamic-view-data="['permissions' => $userPermissions]"
>
    <x-data-table.column title="Total" data-field="total">
        {{ $currency->format($__row->total) }}
    </x-data-table.column>

    <x-data-table.column title="Actions" data-field="id">
        @if($permissions->canEdit)
            <a href="{{ $vm->editUrl($__row->id) }}">Edit</a>
        @endif
    </x-data-table.column>
</x-data-table>
```

#### Choosing Between Them

| Data type | Use | Why |
|---|---|---|
| ViewModels with helper methods | `static-view-data` | Same for all users, avoids wire overhead |
| Currency / number formatters | `static-view-data` | Shared configuration, loaded once from disk |
| App config (`config('app.name')`) | `static-view-data` | Never changes between requests |
| User permissions | `dynamic-view-data` | Different per user, avoids per-user cache files |
| Session or auth state | `dynamic-view-data` | Changes per user |
| Small strings or booleans | Either | Negligible cost both ways |
| Large collections or DTOs | `static-view-data` | Avoids bloating every Livewire round-trip |

#### Where View Data Is Available

Both types are merged and available in all `Blade::render()` contexts:

- Custom column content (between `<x-data-table.column>` tags)
- Custom filter content (between `<x-data-table.filter>` tags)
- Custom search content (between `<x-data-table.search>` tags)
- Footer content (between `<x-data-table.footer>` tags)

#### Constraints

- All values must be **serializable** — scalars, arrays, stdClass, objects implementing `Serializable` or `__serialize()/__unserialize()`. Closures and anonymous classes are not supported.
- For non-serializable dependencies, resolve them from the service container inside the template: `{{ app(MyService::class)->method() }}`.
- View data variables cannot override internal variables (`$__row`, `$loop`, `$attributes`, `$___lwDataTable`). Internal variables always take precedence.

### Row Selection

Add `selectable` to the table to get a column of checkboxes and a selection menu, as in Magento's admin grids:

```blade
<x-data-table :data-src="App\Models\User::class" preset="tailwind3v1" selectable>
    <x-data-table.column title="Name" data-field="name" />
</x-data-table>
```

- Each row has a checkbox; the one in the header checks or unchecks the whole page, and shows as indeterminate when only part of the page is checked.
- The menu next to it offers **Select all (N)** (every row matching the current search and filters, across all pages), **Deselect all**, **Select all on this page** and **Deselect all on this page**. After **Select all**, the rows you uncheck are left out.
- Rows are identified by the `data-identity-column` (`id` by default). Checked rows stay checked as you move between pages, sort the table or change the number of rows per page.
- Changing the search, the filters or a column search drops the selection, since it may no longer match the rows. With `preserve-selection-on-filter`, the rows checked one by one stay checked (a **Select all** is still dropped).
- The counter above the table reads "120 records found (2 selected)". It shows on selectable tables, and on any other table with `records-count` (or hide it with `records-count="false"`). Simple and cursor paginators do not know the total, so only the selected count shows then.
- Checking rows needs no request to the server: the selection travels with the next one. It holds at most `max-selected-ids` ids (config, 10000 by default).

The selection column takes attributes with the `selection-th-`, `selection-td-` and `selection-checkbox-` prefixes. A custom `<x-data-table.footer>` should account for the extra column in its `colspan`s.

### Bulk Actions

Bulk actions run on the selected rows, picked from an "Actions" dropdown above the table (Magento's mass actions). They make the table selectable:

```blade
<x-data-table :data-src="App\Models\Product::class" preset="tailwind3v1">
    <x-data-table.bulk-actions placeholder="Actions">
        <x-data-table.bulk-action name="delete" label="Delete"
            handler="App\DataTable\Products\DeleteProducts"
            confirm="Delete :count product(s)?" can="delete-products" />
        <x-data-table.bulk-action name="status" label="Change status"
            handler="App\DataTable\Products\ChangeStatus@handle"
            :options="['1' => 'Enable', '0' => 'Disable']" />
        <x-data-table.bulk-action name="notify" label="Notify" event="products-notify" keep-selection />
    </x-data-table.bulk-actions>

    <x-data-table.column title="Name" data-field="name" />
</x-data-table>
```

A **handler** is referenced like a [callable data source](#table-with-callable-data-source): `Class` (invokable), `Class@method`, `Class::method`, a function name or a `[Class::class, 'method']` array — never a closure, since the table's definition is cached. It is called through the container, which hands over the parameters it asks for:

- `Selection $selection` (or `$selection`) — the selected rows. In the "Select all" mode, these are all the rows matching the current search and filters, but the ones unchecked;
- `?string $option` — the option picked, for actions with `options` (shown as an `<optgroup>`);
- `BulkAction $action` (or `$action`) — the action's definition;
- anything else the container can resolve.

```php
namespace App\DataTable\Products;

use ErickComp\LivewireDataTable\Selection\Selection;
use ErickComp\LivewireDataTable\Support\ActionResult;

class ChangeStatus
{
    public function handle(Selection $selection, string $option): ActionResult
    {
        $selection->query()->update(['active' => $option === '1']);

        return ActionResult::success(':count product(s) updated');
    }
}
```

`Selection` gives the rows in several ways: `query()` (a builder for the selected rows, on Eloquent and query builder data sources), `rows()`, `lazy()`, `ids()` (checked against the data source), `count()`, and `isAllMatching()`/`selectedIds()`/`excludedIds()` for the raw selection.

What the handler returns decides what the user gets:

| Return value | Result |
|---|---|
| `null` (or anything not listed below) | The default message, "The action was applied to N record(s)." |
| `string` | A success message |
| `ActionResult::success()`, `::info()` or `::error()` | A message of that type. An error leaves the rows selected |
| A redirect (`redirect()->route(...)`, `redirect()->to(...)`) | The page is redirected |
| A download (`response()->download(...)`, `response()->streamDownload(...)`, `Storage::download(...)`) or a `Responsable` making one | The file is downloaded |

`:count` in the messages becomes the number of selected rows. Throwing `ErrorMessageForUserException` shows its message as an error. The messages show above the table until the next request.

An action with **`event`** instead of a handler dispatches that Livewire event with `action`, `option`, `ids` and `count`, for a listener elsewhere on the page to do the work:

```php
#[On('products-notify')]
public function notify(string $action, ?string $option, array $ids, int $count) { /* ... */ }
```

Other details:

- The browser only sends the action's name and option. The server finds the action in the table's definition, checks the option, the gate ability (`can`) and that rows are selected, and resolves the rows itself.
- Actions whose `can` ability the user does not have are left out of the dropdown (and refused, with a 403, if called anyway).
- After the action runs, the selection is cleared, unless the action has `keep-selection` (or the result is an error).
- `confirm` asks for confirmation first: a bare `confirm` asks "Are you sure you want to apply this action to N record(s)?", and a text is the question itself. By default, it uses `window.confirm()`; the preset's `confirm-dialog` key can name another function, `async (message, title) => bool`:

```php
'confirm-dialog' => [
    'function-name' => 'myConfirm',
    // Optional: the code is pushed with the table. Leave it out when the page already defines the function.
    'function-code' => <<<JS
        async function myConfirm(message, title) {
            return (await Swal.fire({ title, text: message, showCancelButton: true })).isConfirmed;
        }
    JS,
],
```

### Row Actions

A column with each row's actions, placed among the columns where it is declared (Magento's actions column):

```blade
<x-data-table :data-src="App\Models\User::class" preset="tailwind3v1" row-click="edit">
    <x-data-table.column title="Name" data-field="name" />

    <x-data-table.row-actions title="Actions" display="dropdown">
        <x-data-table.row-action name="edit" label="Edit" url="/users/{id}/edit" />
        <x-data-table.row-action name="show" label="View" route="users.show" :route-params="['user' => 'id']" target="_blank" />
        <x-data-table.row-action name="archive" label="Archive" route="users.archive" :route-params="['user' => 'id']" method="post" confirm />
        <x-data-table.row-action name="delete" label="Delete" handler="App\DataTable\Users\DeleteUser"
            confirm="Delete {name}?" can="delete" />
        <x-data-table.row-action name="notify" label="Notify" event="user-notify" />
    </x-data-table.row-actions>
</x-data-table>
```

Each action has one destination:

- **`url`** — `{field}` placeholders are filled with the row's values, URL-encoded (`{owner.id}` reaches into relations). Only `http(s)` and relative URLs are accepted;
- **`route`** — with `route-params` mapping each route parameter to a field of the row;
- **`handler`** — a PHP handler, referenced like a [bulk action's](#bulk-actions). It gets `$row` (or the row's class, e.g. `User $user`), `string $id`, `Selection $selection` (just this row) and `RowAction $action`, and returns what a bulk action handler returns: a message, a redirect or a download;
- **`event`** — a Livewire event dispatched with `action` and `id`.

A `url` or `route` action is a link, unless it has another `method` than GET (`post`, `put`, `patch` or `delete`): it then submits a form, with the CSRF token and the `_method` field. `target` applies to both.

- `confirm` asks for confirmation first, as bulk actions do: a bare `confirm` asks "Are you sure?", and a text is the question itself, with the row's values in its `{field}` placeholders.
- `can` is a gate ability checked with the row (`Gate::allows('delete', $user)`, so policies work): the action only shows on the rows where the user passes it, and a handler or event action checks it again on the server.
- The browser only sends the action's name and the row's id. The row must still be among the table's rows (with the current search and filters) when the action runs.
- `display="dropdown"` (the default) shows the actions in a dropdown, `display="links"` side by side. A row with a single action available shows it as a link either way.

**Clicking a row:** `row-click="edit"` on the table runs that row action when the row is clicked (on the rows where the action is available), and `row-click-url="/users/{id}/edit"` leads to that URL instead (Ctrl/Cmd+click opens a new tab). Clicks on links, buttons, form fields, labels and elements marked with `data-lw-dt-no-row-click` keep doing what they do.

### Inline Editing

Rows can be edited in place, one at a time. The table names the handler that saves a row, and each editable column names its input:

```blade
<x-data-table :data-src="App\Models\Product::class" preset="tailwind3v1"
    inline-edit="App\DataTable\Products\SaveProduct" inline-edit-can="update">
    <x-data-table.column title="Name" data-field="name" editor="text" editor-rules="required|max:100|unique:products,name,{id}" />
    <x-data-table.column title="Category" data-field="category_id" editor="select" :editor-options="$categories" />
    <x-data-table.column title="Price" data-field="price" editor="number" editor-rules="required|numeric|min:0" editor-step="0.01" />
    <x-data-table.column title="Active" data-field="active" editor="checkbox" />

    <x-data-table.row-actions>
        <x-data-table.row-action name="show" label="View" route="products.show" :route-params="['product' => 'id']" />
    </x-data-table.row-actions>
</x-data-table>
```

```php
namespace App\DataTable\Products;

use App\Models\Product;

class SaveProduct
{
    public function __invoke(Product $product, array $values): string
    {
        $product->update($values);

        return "{$product->name} saved";
    }
}
```

- **Starting:** with `inline-edit-trigger="button"` (the default), an **Edit** button joins the row actions (or a column of its own, the last one, when the table has none). With `inline-edit-trigger="row-click"`, a click on the row starts editing it; on the rows the user may not edit, the table's `row-click` or `row-click-url` applies instead.
- **Editing:** the editable cells of the row become inputs, and its actions become **Save** and **Cancel**. Enter saves (except in a textarea) and Escape cancels. Editing another row drops the changes made to the previous one, and so does a search, a filter, a sort or a page that leaves the row out.
- **Editors:** `editor` is one of `text`, `number`, `select` (with `:editor-options`, as `value => label`), `date`, `datetime`, `checkbox` and `textarea`. The editor edits the column's `data-field`, or its `editor-field` (a custom column with no `data-field` needs one; `owner.name` reaches into nested fields). Attributes with the `editor-` prefix go to the input (`editor-step`, `editor-placeholder`, `editor-maxlength`...).
- **Validation:** each value is checked against its editor's type (a number, a date, a boolean, one of the options) and the column's `editor-rules`, a string or an array of Laravel validation rules. The rules see the fields by their own names (`after:start_date` works), the messages name the columns by their titles, and `{id}` in a rule becomes the row's id. The messages show under the inputs.
- **Saving:** the handler is referenced like a [bulk action's](#bulk-actions). It gets `array $values` (only the editable fields: numbers as numbers, checkboxes as booleans, an empty number, date or select as `null`), the row as `$row` (or by its class, e.g. `Product $product`) and `string $id`. It returns what a bulk action handler returns: a message, a redirect or a download; nothing shows "Saved.". A `ValidationException` it throws (e.g. `ValidationException::withMessages(['name' => '...'])`) shows its messages under the inputs, and an `ErrorMessageForUserException` keeps the row in editing with an error message.
- **Checks:** the browser only sends the values. The row must still be among the table's rows when it is edited and when it is saved, only the editable fields are read, and `inline-edit-can` is a gate ability checked with the row (`Gate::allows('update', $product)`, so policies work): the rows the user may not edit offer no **Edit**, and the server refuses them (403).

### Refreshing the Table

The rows are fetched again on every request the table makes. When they change elsewhere on the page (a form in a modal, another component), dispatch the `lw-dt-refresh` event to show the change right away:

```js
// In the browser
Livewire.dispatch('lw-dt-refresh', { table: 'products' })
```

```php
// In another Livewire component
$this->dispatch('lw-dt-refresh', table: 'products');
```

`table` is the `id` or `name` given to the `<x-data-table>`; without it, every table on the page refreshes. The search, filters, sorting, page and selection are kept, and the count of a **Select all** is brought up to date.

### Export

An **Export** button downloads the table's rows as a spreadsheet. It needs one of these libraries:

```bash
composer require openspout/openspout          # recommended: writes the rows as they come, with constant memory
composer require phpoffice/phpspreadsheet     # builds the whole spreadsheet in memory before saving it
```

```blade
<x-data-table :data-src="App\Models\Product::class" preset="tailwind3v1" selectable>
    <x-data-table.export formats="csv,xlsx" filename="products-{date}" can="export-products" />

    <x-data-table.column title="Name" data-field="name" />
    <x-data-table.column title="Price" data-field="price">
        <td>$ {{ number_format($__row->price, 2) }}</td>
    </x-data-table.column>
    <x-data-table.column title="Supplier" export-field="supplier.name">
        <td><a href="/suppliers/{{ $__row->supplier_id }}">{{ $__row->supplier->name }}</a></td>
    </x-data-table.column>
    <x-data-table.column title="Internal notes" data-field="notes" exportable="false" />
</x-data-table>
```

- **Rows:** the selected rows, when there are any (the button then reads "Export selected"); otherwise, all the rows matching the search, the filters and the column searches, from every page. Either way, they are sorted like the table.
- **Columns:** all of them, except the row actions column and the ones with `exportable="false"`. The header holds the column titles.
- **Values:** a column exports its `data-field` (for a custom column too: the raw price above, not the formatted one), or the `export-field` when it has one. Numbers stay numbers; dates, enums and other values become the text the table shows. A custom column with neither exports what it renders, as plain text: its Blade is rendered for every row, which costs much more than reading a field.
- **Formats:** `csv`, `xlsx` and `ods` (`formats` defaults to `csv,xlsx`). With a single format, the button downloads it right away; with more, it opens a menu. CSV files are UTF-8 with a BOM (so Excel reads the accents right), with the `export.csv-delimiter` of the config (`,` by default; Excel expects `;` in locales that write decimals with a comma).
- **File name:** `filename` without the extension, where `{date}` and `{datetime}` become the current ones (`export-{date}` by default).
- **Permission:** with `can`, users without the gate ability do not see the button, and the server refuses the export (403).
- **Formula injection:** a spreadsheet runs a CSV value starting with `=`, `+`, `-` or `@` as a formula. Such text gets a leading `'` (`export.escape-formulas`, on by default), except text holding just a number, like `-12.5`. XLSX and ODS files always store text as text.

Rows are read from the data source in chunks of 1,000, using the `data-identity-column` (`id` by default) to keep their order steady: on a query data source, that column must exist. The file is written during the Livewire request, so the request's time limit bounds how many rows fit:

- `max-execution-time="300"` on the `<x-data-table.export>` sets PHP's `max_execution_time` (with `ini_set()`) while the rows are counted and written, and puts the previous value back afterwards (a long-lived worker, as under Octane, keeps its own). It does not lift the limits outside PHP: the web server's or the proxy's timeouts, or PHP-FPM's `request_terminate_timeout`;
- `export.max-rows` (config) refuses larger exports with a message;
- the table's `php-max-memory` applies.

**The download.** The finished file does not travel inside Livewire's response (which would hold it whole in memory, base64-encoded, on the server and in the browser). It goes to a filesystem disk, and the browser downloads it from a route of the package, streamed:

- the URL is signed, lasts `export.download-ttl` minutes (5 by default) and only works in the session that made the export;
- the file is deleted once downloaded, so the URL works once; files never downloaded are deleted by a later export, once their URL has expired;
- `export.disk` and `export.directory` (`local` and `lw-data-table-exports` by default) tell where the files wait. **With more than one server**, use a disk they all reach (S3, for instance): the export and its download may land on different servers;
- `export.route.prefix` and `export.route.middleware` (`lw-data-table` and `['web']` by default) set the route, `/lw-data-table/export/{file}`. The middleware must start the session.

The library is picked by `export.writer` (config): `auto` (the default: OpenSpout when installed, PhpSpreadsheet otherwise), `openspout`, `phpspreadsheet`, or the name of your own class implementing `ErickComp\LivewireDataTable\Export\ExportWriter`.

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

The table starts with the first option, in the order given. The rows per page asked by the browser or the URL must be one of the options; any other value falls back to the first one.

### State and URL

The search, filters, column searches, sorting, rows per page and page are kept in the URL as they change, so the address of the page reproduces what is on the screen: bookmark it or share it to come back to the same view (Magento's grid bookmarks). Values left at their defaults stay out of the URL.

The parameter names can be changed per table, in the preset (`query-string-*` keys) or in the config:

| Table attribute | Config / preset key | Default |
|---|---|---|
| `search-name` | `query-string-search` | `search` |
| `filters-name` | `query-string-filters` | `filters` |
| `columns-search-name` | `query-string-param-cols-search` | `cols-search` |
| `per-page-name` | `query-string-per-page` | `per-page` |
| `page-name` | `query-string-page-name` | `page` |

Sorting uses `sortBy` and `sortDir`. Give tables on the same page different names, or their parameters will mix.

To come back to the last state when the URL has none of the table's parameters (e.g. after following a menu link), add `remember-state` and a `state-key` that tells the table apart from the others:

```blade
<x-data-table :data-src="App\Models\User::class" state-key="users" remember-state>
    ...
</x-data-table>
```

The search, filters, column searches, sorting and rows per page are kept in the session (under `lw-dt-state.users`); the page and the selection are not. The remembered state shows up in the URL as soon as the table loads, and a URL with any of the table's parameters wins over it. What comes back from the session is checked against the table's definition, like any input: unknown filters and columns, sorting by a column that is not sortable and rows per page that are not an option are dropped.

### Loading Delay

Control when the loading overlay appears using Livewire delay modifiers:

```blade
<x-data-table :data-src="App\Models\User::class" loading-delay-modifier="short">
    ...
</x-data-table>
```

Valid values: `shortest`, `shorter`, `short`, `long`, `longer`, `longest`.

### Disable Loader Overlay

Opt out of the preset's loading spinner overlay for a specific table, without switching presets:

```blade
<x-data-table :data-src="App\Models\User::class" disable-loader-overlay>
    ...
</x-data-table>
```

Note: the overlay's CSS is still emitted once, page-wide, if any other `<x-data-table>` on the page uses the same preset with the overlay enabled.

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
| `per-page-name` | `string` | `'per-page'` | Query string parameter for the rows per page |
| `columns-search-debounce` | `int` | `250` | Debounce in ms for column search |
| `pagination-view` | `string` | Preset default | Custom pagination view |
| `loading-delay-modifier` | `string` | `null` | Livewire loading delay modifier |
| `disable-loader-overlay` | `bool` | `false` | Disable the preset's loading spinner overlay for this table |
| `selectable` | `bool` | `false` | Adds row selection: a column of checkboxes and the selection menu |
| `preserve-selection-on-filter` | `bool` | `false` | Keep the rows checked one by one when the search or the filters change |
| `records-count` | `bool` | Same as `selectable` | Show the "N records found (M selected)" counter |
| `row-click` | `string` | `null` | Name of the row action run when a row is clicked |
| `row-click-url` | `string` | `null` | URL a click on a row leads to, with `{field}` placeholders |
| `state-key` | `string` | `null` | Steady name of the table (letters, digits, `-` and `_`), e.g. to remember its state |
| `remember-state` | `bool` | `false` | Remember the search, filters, sorting and rows per page in the session (needs `state-key`) |
| `inline-edit` | `string\|array` | `null` | Handler that saves a row edited inline: turns [inline editing](#inline-editing) on |
| `inline-edit-trigger` | `string` | `'button'` | What starts editing a row: `button` (an **Edit** button) or `row-click` |
| `inline-edit-can` | `string` | `null` | Gate ability, checked with the row, required to edit it |
| `data-identity-column` | `string` | `'id'` | Row identity column |
| `php-max-memory` | `string` | `null` | PHP memory_limit override |
| `dynamic-view-data` | `array` | `[]` | Per-user/per-request variables passed to Blade renderers (encrypted, travels over the wire) |
| `static-view-data` | `array` | `[]` | Shared variables passed to Blade renderers (compiled into cache file, zero wire overhead) |
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
| `selection-th-` | Selection column `<th>` |
| `selection-td-` | Selection column `<td>` |
| `selection-checkbox-` | Selection checkboxes |
| `tbody-tr-` | Body `<tr>` |
| _(no prefix)_ | `<table>` |

### `<x-data-table.column>`

| Attribute | Type | Default | Description |
|---|---|---|---|
| `title` | `string` | **required** | Column header text |
| `data-field` | `string` | `null` | Data key to display |
| `sortable` | `bool` | `false` | Enable sorting |
| `searchable` | `bool\|string` | `false` | Enable per-column search (`true` or mode) |
| `exportable` | `bool` | `true` | Whether the column goes into the [exports](#export) |
| `export-field` | `string` | `null` | Row field exported in place of what the column shows |
| `editor` | `string` | `null` | [Inline editing](#inline-editing) input: `text`, `number`, `select`, `date`, `datetime`, `checkbox` or `textarea` |
| `editor-options` | `array` | `[]` | Options of a `select` editor, as `value => label` |
| `editor-rules` | `string\|array` | `[]` | Validation rules of the edited value (`{id}` becomes the row's id) |
| `editor-field` | `string` | From `data-field` | Row field the editor edits |

Supports `th-*`, `td-*`, `th-search-input-*` and `editor-*` (the inline editor's input) prefixes for forwarding attributes.

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

### `<x-data-table.bulk-actions>`

A direct child of `<x-data-table>`, at most one per table. See [Bulk Actions](#bulk-actions).

| Attribute | Type | Default | Description |
|---|---|---|---|
| `placeholder` | `string` | "Actions" (translated) | The dropdown's empty option |

Other attributes go to the `<select>`.

### `<x-data-table.bulk-action>`

A self-closing direct child of `<x-data-table.bulk-actions>`.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `name` | `string` | **required** | Unique name, sent by the browser to run the action |
| `label` | `string` | From `name` | Text in the dropdown |
| `handler` | `string\|array` | — | PHP handler (see [Bulk Actions](#bulk-actions)). Use either `handler` or `event` |
| `event` | `string` | — | Livewire event dispatched instead of calling a handler |
| `options` | `array` | `[]` | Sub-actions, as `value => label`: the action becomes an `<optgroup>` and the handler gets the value as `$option` |
| `confirm` | `bool\|string` | `false` | Ask for confirmation first: `true` for the default question, or the question (`:count` is replaced) |
| `confirm-title` | `string` | "Confirm" (translated) | Title handed over to the preset's `confirm-dialog` function |
| `can` | `string` | `null` | Gate ability required to see and run the action |
| `keep-selection` | `bool` | `false` | Keep the rows selected after the action runs |

Other attributes go to the `<option>` (or `<optgroup>`).

### `<x-data-table.row-actions>`

A direct child of `<x-data-table>`, at most one per table, placed among the columns. See [Row Actions](#row-actions).

| Attribute | Type | Default | Description |
|---|---|---|---|
| `title` | `string` | "Actions" (translated) | Column header text |
| `display` | `string` | `'dropdown'` | `dropdown` or `links` |

Supports the `th-*` and `td-*` prefixes, like a column.

### `<x-data-table.row-action>`

A self-closing direct child of `<x-data-table.row-actions>`.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `name` | `string` | **required** | Unique name, sent by the browser to run the action |
| `label` | `string` | From `name` | Text of the link or button |
| `url` | `string` | — | URL with `{field}` placeholders. Use one of `url`, `route`, `handler` and `event` |
| `route` | `string` | — | Route name |
| `route-params` | `array` | `[]` | Route parameter => row field |
| `handler` | `string\|array` | — | PHP handler run on the server |
| `event` | `string` | — | Livewire event dispatched with `action` and `id` |
| `method` | `string` | `'get'` | HTTP method of a `url` or `route` action: anything but `get` submits a form |
| `target` | `string` | `null` | Target of a `url` or `route` action, e.g. `_blank` |
| `confirm` | `bool\|string` | `false` | Ask for confirmation first: `true` for the default question, or the question (`{field}` is replaced) |
| `confirm-title` | `string` | "Confirm" (translated) | Title handed over to the preset's `confirm-dialog` function |
| `can` | `string` | `null` | Gate ability checked with the row |

Other attributes go to the link or button.

### `<x-data-table.export>`

A self-closing direct child of `<x-data-table>`, at most one per table. See [Export](#export).

| Attribute | Type | Default | Description |
|---|---|---|---|
| `formats` | `string\|array` | `'csv,xlsx'` | Formats offered: `csv`, `xlsx` and `ods` |
| `filename` | `string` | `'export-{date}'` | Name of the file, without the extension (`{date}` and `{datetime}` are replaced) |
| `label` | `string` | "Export" (translated) | Text of the button |
| `selected-label` | `string` | "Export selected" (translated) | Text of the button while rows are selected |
| `can` | `string` | `null` | Gate ability required to see the button and export |
| `max-execution-time` | `int` | `null` | PHP's `max_execution_time`, in seconds (`0` for no limit), while the rows are counted and written. Without it, PHP's own applies |

Other attributes go to the button.

### `<x-data-table.assets>`

No attributes — accepts arbitrary CSS/JS content as its slot. See [Custom Assets](#custom-assets).

### `<x-data-table.scripts>`

No attributes — accepts arbitrary JavaScript content as its slot. See [Custom Scripts](#custom-scripts).

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
| `withoutPagination()` | A copy of the params whose pagination methods above return every row in a single page |
| `isPaginationDisabled()` | Whether the params come from `withoutPagination()` |

The data table sometimes needs every row matching the current search and filters, not just the current page (to resolve rows selected across pages, for instance). It then calls your callable with `withoutPagination()` params, so a callable that paginates through these methods returns all the rows. A callable that paginates on its own should check `$params->isPaginationDisabled()` and skip pagination in that case:

```php
public function getUsers(LwDataRetrievalParams $params)
{
    $query = $params->apply(User::query());

    return $params->isPaginationDisabled()
        ? $query->get()
        : $query->paginate($params->perPage);
}
```

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
- `actions` — search, filters, per-page, records counter and bulk actions container
- `search` — search input and button styling (with icon configuration)
- `filters` — filter container, toggle button, apply button, individual filter items, and input styling per type
- `applied-filters` — active filter badges, removal buttons and the "Clear all" button (`button-clear-all`)
- `table` — wrapper, thead, tbody (including the selected and clickable row classes), tfoot, th sorting indicators, the selection column, checkboxes and menu (`table.selection`), and the row actions links, forms and dropdown (`table.row-actions`)
- `loader-overlay` — loading spinner template and CSS
- `pagination` — views, container classes, per-page defaults
- `messages` — the messages shown above the table (`container`, `success`, `error`, `info`, `button-dismiss`)
- `confirm-dialog` — the JavaScript function asked to confirm an action (`function-name`, `function-code`); `window.confirm()` by default
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

### Demo App and Browser Tests

The repository ships a [Testbench workbench](https://packages.tools/testbench) app (`workbench/`) with a demo page listing 120 seeded products. To try the table in a browser:

```bash
composer serve
```

Then open `http://127.0.0.1:8000/demo`, `/demo/bootstrap4v1` or `/demo/tailwind3v1` (the last two load Bootstrap and Tailwind from a CDN). Add `?pagination=simple`, `cursor` or `none` to try the other pagination types.

The same app backs the browser tests in `tests/Browser`, written with [Pest's browser testing plugin](https://pestphp.com/docs/browser-testing). They need Node and Playwright, so they are not part of `composer test`:

```bash
npm install
npx playwright install chromium
composer test:browser
```

Screenshots of failed browser tests are saved to `tests/Browser/Screenshots`.

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Write tests for your changes
4. Run the test suite (`composer test`), plus the browser tests (`composer test:browser`) for UI changes
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).

## Credits

- [Erick de Azevedo Lima](https://github.com/erickcomp)
- [All Contributors](../../contributors)

Built with [Laravel](https://laravel.com) and [Livewire](https://livewire.laravel.com).
