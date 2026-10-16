{{--
    The export button. With a single format it downloads the file right away; with more, it opens a menu with an entry
    per format, positioned "fixed" next to the button like the selection menu. While rows are selected, only they are
    exported, and the button says so.

    Labels are escaped without double encoding: bound attributes (":label") come already escaped by Blade.

    @var \ErickComp\LivewireDataTable\DataTable\Export $export
    @var bool $selectable
    @var \ErickComp\LivewireDataTable\Livewire\Preset $preset
--}}
@php
    $escape = fn(string $value): string => e($value, false);
    $buttonAttributes = $export->attributes->class($preset->get('actions.bulk-actions-and-per-page.export.button.class', []));
    $singleFormat = count($export->formats) === 1 ? $export->formats[0] : null;
@endphp
@if ($singleFormat !== null)
    <button type="button" {{ $buttonAttributes }}
        x-on:click="$wire.export({{ \Illuminate\Support\Js::from($singleFormat) }})">
        <span @if ($selectable) x-show="selectedCount === 0" @endif>{!! $escape($export->label()) !!}</span>
        @if ($selectable)
            <span x-show="selectedCount > 0" x-cloak>{!! $escape($export->selectedLabel()) !!}</span>
        @endif
        ({{ \strtoupper($singleFormat) }})
    </button>
@else
    <span @class($preset->get('actions.bulk-actions-and-per-page.export.menu-container.class', []))
        x-data="{
            exportMenuOpen: false,
            exportMenuStyle: {},
            toggleExportMenu() {
                this.exportMenuOpen = !this.exportMenuOpen;

                if (this.exportMenuOpen) {
                    const rect = this.$refs.exportMenuButton.getBoundingClientRect();
                    this.exportMenuStyle = { position: 'fixed', top: rect.bottom + 'px', left: rect.left + 'px', zIndex: 50 };
                }
            },
        }"
        x-on:click.outside="exportMenuOpen = false"
        x-on:keydown.escape.window="exportMenuOpen = false"
        x-on:scroll.window="exportMenuOpen = false"
        x-on:resize.window="exportMenuOpen = false">
        <button type="button" {{ $buttonAttributes->class($preset->get('actions.bulk-actions-and-per-page.export.menu-button.class', [])) }}
            x-ref="exportMenuButton"
            aria-haspopup="menu"
            x-bind:aria-expanded="exportMenuOpen"
            x-on:click="toggleExportMenu()">
            <span @if ($selectable) x-show="selectedCount === 0" @endif>{!! $escape($export->label()) !!}</span>
            @if ($selectable)
                <span x-show="selectedCount > 0" x-cloak>{!! $escape($export->selectedLabel()) !!}</span>
            @endif
            {!! $preset->get('actions.bulk-actions-and-per-page.export.menu-button.icon', '&#9662;') !!}
        </button>

        <ul role="menu" x-show="exportMenuOpen" x-cloak x-bind:style="exportMenuStyle"
            @class($preset->get('actions.bulk-actions-and-per-page.export.menu.class', []))>
            @foreach ($export->formats as $format)
                <li role="none">
                    <button type="button" role="menuitem" @class($preset->get('actions.bulk-actions-and-per-page.export.menu-item.class', []))
                        x-on:click="exportMenuOpen = false; $wire.export({{ \Illuminate\Support\Js::from($format) }})">
                        {{ $export->formatLabel($format) }}
                    </button>
                </li>
            @endforeach
        </ul>
    </span>
@endif
