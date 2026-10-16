{{--
    The inline editing buttons of a row: "Edit" (the "button" trigger), or "Save" and "Cancel" on the row being edited.

    @var bool $editing Whether the row is the one being edited
    @var string $rowId
    @var array $class The preset's classes for the "Edit" button
    @var ?string $role "menuitem" in a dropdown
    @var \ErickComp\LivewireDataTable\Livewire\Preset $preset
--}}
@if ($editing)
    <span @class($preset->get('table.inline-edit.buttons.class', []))>
        <button type="button" @class($preset->get('table.inline-edit.button-save.class', []))
            wire:loading.attr="disabled" wire:target="saveInlineEdit"
            x-on:click="$wire.saveInlineEdit()">{{ __('erickcomp_lw_data_table::messages.inline_edit_save') }}</button>
        <button type="button" @class($preset->get('table.inline-edit.button-cancel.class', []))
            x-on:click="$wire.cancelInlineEdit()">{{ __('erickcomp_lw_data_table::messages.inline_edit_cancel') }}</button>
    </span>
@else
    <button type="button" data-lw-dt-inline-edit @class($class)@if ($role !== null) role="{{ $role }}"@endif
        x-on:click="$wire.editRow({{ \Illuminate\Support\Js::from($rowId) }})">{{ __('erickcomp_lw_data_table::messages.inline_edit_button') }}</button>
@endif
