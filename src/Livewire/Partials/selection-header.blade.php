{{--
    Header of the selection column: the checkbox for the rows on the page and the selection menu.

    @var \Illuminate\View\ComponentAttributeBag $thAttributes
    @var \Illuminate\View\ComponentAttributeBag $checkboxAttributes
    @var string[] $pageRowsIds
    @var ?int $recordsTotal
    @var \ErickComp\LivewireDataTable\Livewire\Preset $preset

    The menu is positioned "fixed" next to its button: the table wrapper may clip overflowing content (overflow: hidden
    or auto), which an absolutely positioned menu would not escape.
--}}
<th {{ $thAttributes }}
    x-data="{
        pageRowsIds: @js($pageRowsIds),
        selectionMenuOpen: false,
        selectionMenuStyle: {},
        toggleSelectionMenu() {
            this.selectionMenuOpen = !this.selectionMenuOpen;

            if (this.selectionMenuOpen) {
                const rect = this.$refs.selectionMenuButton.getBoundingClientRect();
                this.selectionMenuStyle = { position: 'fixed', top: rect.bottom + 'px', left: rect.left + 'px', zIndex: 50 };
            }
        },
    }">
    <input type="checkbox"
        {{ $checkboxAttributes->class($preset->get('table.selection.checkbox.class', [])) }}
        aria-label="{{ __('erickcomp_lw_data_table::messages.select_page_rows') }}"
        x-bind:checked="pageSelectionState(pageRowsIds) === 'all'"
        x-effect="$el.indeterminate = pageSelectionState(pageRowsIds) === 'some'"
        x-bind:disabled="pageRowsIds.length === 0"
        x-on:change="pageSelectionState(pageRowsIds) === 'all' ? deselectPage(pageRowsIds) : selectPage(pageRowsIds)" />

    <span @class($preset->get('table.selection.menu-container.class', []))
        x-on:click.outside="selectionMenuOpen = false"
        x-on:keydown.escape.window="selectionMenuOpen = false"
        x-on:scroll.window="selectionMenuOpen = false"
        x-on:resize.window="selectionMenuOpen = false">
        <button type="button"
            x-ref="selectionMenuButton"
            @class($preset->get('table.selection.menu-button.class', []))
            aria-haspopup="menu"
            x-bind:aria-expanded="selectionMenuOpen"
            aria-label="{{ __('erickcomp_lw_data_table::messages.selection_options') }}"
            title="{{ __('erickcomp_lw_data_table::messages.selection_options') }}"
            x-on:click="toggleSelectionMenu()">
            {!! $preset->get('table.selection.menu-button.icon', '&#9662;') !!}
        </button>

        <ul role="menu" x-show="selectionMenuOpen" x-cloak x-bind:style="selectionMenuStyle"
            @class($preset->get('table.selection.menu.class', []))>
            <li role="none">
                <button type="button" role="menuitem" @class($preset->get('table.selection.menu-item.class', []))
                    x-on:click="selectionMenuOpen = false; selectAllMatching()">
                    {{ $recordsTotal === null
                        ? __('erickcomp_lw_data_table::messages.select_all')
                        : __('erickcomp_lw_data_table::messages.select_all_count', ['count' => $recordsTotal]) }}
                </button>
            </li>
            <li role="none">
                <button type="button" role="menuitem" @class($preset->get('table.selection.menu-item.class', []))
                    x-on:click="selectionMenuOpen = false; deselectAll()">
                    {{ __('erickcomp_lw_data_table::messages.deselect_all') }}
                </button>
            </li>
            <li role="none">
                <button type="button" role="menuitem" @class($preset->get('table.selection.menu-item.class', []))
                    x-on:click="selectionMenuOpen = false; selectPage(pageRowsIds)">
                    {{ __('erickcomp_lw_data_table::messages.select_page') }}
                </button>
            </li>
            <li role="none">
                <button type="button" role="menuitem" @class($preset->get('table.selection.menu-item.class', []))
                    x-on:click="selectionMenuOpen = false; deselectPage(pageRowsIds)">
                    {{ __('erickcomp_lw_data_table::messages.deselect_page') }}
                </button>
            </li>
        </ul>
    </span>
</th>
