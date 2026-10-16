{{--
    The row actions cell: the actions side by side ("links" display, or a single action, as in Magento's grids) or a
    dropdown. The dropdown is positioned "fixed" next to its button, like the selection menu: the table wrapper may
    clip overflowing content.

    @var \ErickComp\LivewireDataTable\DataTable\RowActionsColumn $column
    @var \ErickComp\LivewireDataTable\DataTable\RowAction[] $actions The ones the user may run on this row
    @var mixed $row
    @var string $rowId
    @var \Illuminate\View\ComponentAttributeBag $tdAttributes
    @var \ErickComp\LivewireDataTable\Livewire\Preset $preset
--}}
<td {{ $tdAttributes }}>
    @if (count($actions) === 1 || (count($actions) > 1 && !$column->isDropdown()))
        <span @class($preset->get('table.row-actions.links.class', []))>
            @foreach ($actions as $action)
                @include('erickcomp_lw_data_table::row-action', [
                    'class' => $preset->get('table.row-actions.link.class', []),
                    'role' => null,
                ])
            @endforeach
        </span>
    @elseif (count($actions) > 1)
        <span @class($preset->get('table.row-actions.menu-container.class', []))
            data-lw-dt-no-row-click
            x-data="{
                rowActionsOpen: false,
                rowActionsStyle: {},
                toggleRowActions() {
                    this.rowActionsOpen = !this.rowActionsOpen;

                    if (this.rowActionsOpen) {
                        const rect = this.$refs.rowActionsButton.getBoundingClientRect();
                        this.rowActionsStyle = { position: 'fixed', top: rect.bottom + 'px', left: rect.left + 'px', zIndex: 50 };
                    }
                },
            }"
            x-on:click.outside="rowActionsOpen = false"
            x-on:keydown.escape.window="rowActionsOpen = false"
            x-on:scroll.window="rowActionsOpen = false"
            x-on:resize.window="rowActionsOpen = false">
            <button type="button"
                x-ref="rowActionsButton"
                @class($preset->get('table.row-actions.menu-button.class', []))
                aria-haspopup="menu"
                x-bind:aria-expanded="rowActionsOpen"
                x-on:click="toggleRowActions()">
                {{ __('erickcomp_lw_data_table::messages.row_actions_menu_button') }}
                {!! $preset->get('table.row-actions.menu-button.icon', '&#9662;') !!}
            </button>

            <ul role="menu" x-show="rowActionsOpen" x-cloak x-bind:style="rowActionsStyle" x-on:click="rowActionsOpen = false"
                @class($preset->get('table.row-actions.menu.class', []))>
                @foreach ($actions as $action)
                    <li role="none">
                        @include('erickcomp_lw_data_table::row-action', [
                            'class' => $preset->get('table.row-actions.menu-item.class', []),
                            'role' => 'menuitem',
                        ])
                    </li>
                @endforeach
            </ul>
        </span>
    @endif
</td>
