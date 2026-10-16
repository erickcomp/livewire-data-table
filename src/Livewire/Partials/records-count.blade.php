{{--
    "N records found (M selected)". The selected count changes in the browser as rows are (un)checked, so its text is
    built there, from the singular and plural forms of the translation.

    @var ?int $recordsTotal
    @var bool $selectable
    @var \ErickComp\LivewireDataTable\Livewire\Preset $preset
--}}
<div @class($preset->get('actions.bulk-actions-and-per-page.records-count.class', []))
    @if ($selectable)
        x-data="{
            selectedCountLabels: @js([
                'one' => trans_choice('erickcomp_lw_data_table::messages.records_selected', 1, ['count' => '{count}']),
                'other' => trans_choice('erickcomp_lw_data_table::messages.records_selected', 2, ['count' => '{count}']),
            ]),
        }"
    @endif
    >
    @if ($recordsTotal !== null)
        <span>{{ trans_choice('erickcomp_lw_data_table::messages.records_found', $recordsTotal, ['count' => $recordsTotal]) }}</span>
    @endif

    @if ($selectable)
        <span x-show="selectedCount > 0" x-cloak
            x-text="(selectedCount === 1 ? selectedCountLabels.one : selectedCountLabels.other).replace('{count}', selectedCount)"></span>
    @endif
</div>
