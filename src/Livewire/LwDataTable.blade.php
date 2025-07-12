<?php

use ErickComp\LivewireDataTable\DataTable\CustomRenderedColumn;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;


/** @var \ErickComp\LivewireDataTable\DataTable $dataTable */
/** @var \ErickComp\LivewireDataTable\Livewire\LwDataTable $___lwDataTable */
/** @var \ErickComp\LivewireDataTable\DataTable\Filter $filterItem */

$thAttributes = function ($columnThAttributes, $tableThAttributes): ComponentAttributeBag {
    return $columnThAttributes->merge($tableThAttributes->all());
};

//$onClickSortableColumn = function (BaseColumn $column): string {
//    return $column->isSortable() ? 'wire:click="setSortBy(\'' . $column->name . '\')"' : '';
//};
?>
<div {{ $dataTable->containerAttributes->class([...$___lwDataTable->preset()->get('main-container.class'), 'lw-dt']) }}
    x-data="{!! $___lwDataTable->xData() !!}">
    @if($dataTable->hasTableActions())
        <div @class($___lwDataTable->preset()->get('actions.container.class'))>
            <div @class($___lwDataTable->preset()->get('actions.row.class'))>
                @if($dataTable->isSearchable())
                    <div {{ $dataTable->search->componentAttributes->class($___lwDataTable->preset()->get('search.container.class')) }}>
                        @if ($dataTable->search->hasCustomRenderer()))
                            @php
                                $searchViewData = [
                                    '__dataTable' => $dataTable,
                                    '___lwDataTable' => $___lwDataTable,
                                ];
                            @endphp
                            {!! Blade::render($dataTable->search->customRendererCode, $searchViewData) !!}
                            @php unset($searchViewData); @endphp
                        @else
                            <input {{ $dataTable->search->inputAttributes->class($___lwDataTable->preset()->get('search.input.class')) }} />

                            <button {{ $dataTable->search->buttonAttributes->class($___lwDataTable->preset()->get('search.button.class')) }}>
                                @if($dataTable->search->shouldShowIconOnApplyButton())
                                    {!! $___lwDataTable->preset()->get('search.button.icon') !!}
                                @endif
                                @lang('erickcomp_lw_data_table::messages.search_button_label')
                            </button>
                        @endif
                    </div>
                @endif

                @if($dataTable->isFilterable() && $dataTable->filters->isCollapsible())
                    <button {{ $dataTable->filters->buttonToggleAttributes->class([...$___lwDataTable->preset()->get('filters.toggle-button.class'), 'active' => $___lwDataTable->shouldShowFiltersContainer()]) }}
                        x-bind:class="{ 'active': filtersContainerIsOpen }">

                        @if($dataTable->filters->shouldShowIconOnToggleButton())
                            {!! $___lwDataTable->preset()->get('filters.toggle-button.icon') !!}
                        @endif
                        
                        @lang('erickcomp_lw_data_table::messages.toggle_filters_button_label')
                    </button>
                @endif
            </div> <!-- end: lw-dt-table-actions-row -->
            @if($dataTable->isFilterable())
                <div @class($___lwDataTable->preset()->get('actions.row.class'))>
                    <div {{ $dataTable->filters->containerAttributes()->class($___lwDataTable->preset()->get('filters.container.class')) }}>
                        @if(!$dataTable->filters->isCollapsible())
                            <span @class($___lwDataTable->preset()->get('filters.title.class'))>
                                {{ $dataTable->filters->title() }}
                            </span>
                        @endif
                        @php $renderedFilterItemsNames = []; @endphp
                        @foreach($dataTable->filters->filtersItems as $filterItem)
                            <div @class($___lwDataTable->preset()->get('filters.item.class'))>
                                <div @class([
                                    ...$___lwDataTable->preset()->get('filters.item.content.class') ,
                                    ...($filterItem->mode === Filter::MODE_RANGE ? $___lwDataTable->preset()->get('filters.item.content.range.class') : [])
                                    ])>
                                    @if(!empty($filterItem->customRendererCode))
                                        {!! $filterItem->getCustomRendererCodeWithXModel('inputFilters') !!}
                                    @else
                                        @php
                                            if (\in_array($filterItem->attributes['name'], $renderedFilterItemsNames)) {
                                                throw new \LogicException("Each filter item must have a unique name. Duplicated name found: [{$filterItem->attributes['name']}]");
                                            }

                                            $renderedFilterItemsNames[] = $filterItem->attributes['name'];
                                        @endphp

                                        <legend @class($___lwDataTable->preset()->get('filters.item.content.legend.class'))>
                                            {{-- ilterItem-?label . ?({$filterItem-?inputType})? --}}
                                            <span @class($___lwDataTable->preset()->get('filters.item.content.legend.span.class'))>
                                                {{-- ilterItem-?label . ?({$filterItem-?inputType})? --}}
                                                {{ $filterItem->label }}
                                            </span>
                                        </legend>

                                        @if(\in_array($filterItem, [Filter::TYPE_SELECT, Filter::TYPE_SELECT_MULTIPLE], true))
                                            <select {{ $filterItem->inputAttributes(except: 'name')->class($___lwDataTable->preset()->get('filters.item.content.select.class')) }}
                                                name="{{ $filterItem->buildInputNameAttribute($___lwDataTable->filtersUrlParam()) }}"
                                                {{-- wire:model="{{ $filterItem->buildWireModelAttribute('inputFilters') }}" --}}
                                                {{-- x-model="{{ $filterItem->buildWireModelAttribute('inputFilters') }}"> --}}
                                                {{-- x-on:input="updateFilterInput('{{ $filterItem->buildWireModelAttribute('inputFilters')
                                                }}')" --}}
                                                x-model="{{ $filterItem->buildXModelAttribute('inputFilters') }}"
                                                @foreach($filterItem->getSelectOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            @if($filterItem->mode === Filter::MODE_RANGE)
                                                @php
                                                    $inputFromClasses = new ComponentAttributeBag(
                                                    [ 'class' => Arr::toCssClasses($___lwDataTable->preset()->get("filters.item.content.input-{$filterItem->htmlInputType()}.class", []))]
                                                    )->class($___lwDataTable->preset()->get("filters.item.content.range.input.from.class", []));

                                                    $inputToClasses = new ComponentAttributeBag(
                                                    [ 'class' => Arr::toCssClasses($___lwDataTable->preset()->get("filters.item.content.input-{$filterItem->htmlInputType()}.class", []))]
                                                    )->class($___lwDataTable->preset()->get("filters.item.content.range.input.to.class", []));
                                                @endphp
                                                <span @class($___lwDataTable->preset()->get("filters.item.content.range.label.from.class", ''))>
                                                    @lang('erickcomp_lw_data_table::messages.range_filter_label_from'):
                                                </span>
                                                <input
                                                    type="{{ $filterItem->htmlInputType() }}"
                                                    {{ $filterItem->inputAttributes(except: 'name')->class($inputFromClasses['class']) }}
                                                    name="{{ $filterItem->buildInputNameAttribute($___lwDataTable->filtersUrlParam(), 'from') }}"
                                                    {{-- wire:model="{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}" --}}
                                                    {{-- x-model="{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}" --}}
                                                    {{--
                                                    x-on:input="updateFilterInput('{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}')"
                                                    --}}
                                                    x-model="{{ $filterItem->buildXModelAttribute('inputFilters', 'from') }}"
                                                    x-on:keydown.enter="applyFilters()">
                                                <span @class($___lwDataTable->preset()->get("filters.item.content.range.label.to.class", ''))>
                                                    @lang('erickcomp_lw_data_table::messages.range_filter_label_to'):
                                                </span>
                                                <input
                                                    type="{{ $filterItem->htmlInputType() }}"
                                                    {{ $filterItem->inputAttributes(except: 'name')->class($inputToClasses['class']) }}
                                                    name="{{ $filterItem->buildInputNameAttribute($___lwDataTable->filtersUrlParam(), 'to') }}"
                                                    {{-- wire:model="{{ $filterItem->buildWireModelAttribute('inputFilters', 'to') }}" --}}
                                                    {{-- x-model="{{ $filterItem->buildWireModelAttribute('inputFilters', 'to') }}" --}}
                                                    {{--
                                                    x-on:input="updateFilterInput('{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}')"
                                                    --}}
                                                    x-model="{{ $filterItem->buildXModelAttribute('inputFilters', 'to') }}"
                                                    x-on:keydown.enter="applyFilters()">
                                            @else
                                                <input
                                                    type="{{ $filterItem->htmlInputType() }}"
                                                    {{ $filterItem->inputAttributes(except: 'name')->class($___lwDataTable->preset()->get("filters.item.content.input-{$filterItem->inputType}.class", '')) }}
                                                    name="{{ $filterItem->buildInputNameAttribute($___lwDataTable->filtersUrlParam()) }}"
                                                    {{-- wire:model="{{ $filterItem->buildWireModelAttribute('inputFilters') }}" --}}
                                                    {{-- x-model="{{ $filterItem->buildWireModelAttribute('inputFilters') }}" --}}
                                                    {{--
                                                    x-on:input="updateFilterInput('{{ $filterItem->buildWireModelAttribute('inputFilters') }}')"
                                                    --}}
                                                    x-model="{{ $filterItem->buildXModelAttribute('inputFilters') }}"
                                                    x-on:keydown.enter="applyFilters()">
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <div @class($___lwDataTable->preset()->get('filters.apply-button.container.class'))>
                            {{-- <button wire:click="applyFilters()" {{ $dataTable->filters->buttonApplyAttributes }}> --}}
                            <button {{ $dataTable->filters->buttonApplyAttributes->class($___lwDataTable->preset()->get('filters.apply-button.class')) }}>
                                @lang('erickcomp_lw_data_table::messages.apply_filters_button_label')
                            </button>
                        </div>
                    </div> {{-- end: $dataTable->filters->containerAttributes --}}

                </div>
            @endif

            @if (!empty($search) || (!empty($dataTable->filters) && !empty($___lwDataTable->appliedFiltersData())))
                <div @class($___lwDataTable->preset()->get('actions.row.class'))>
                    <div @class($___lwDataTable->preset()->get('applied-filters.container.class')) >
                        @if(count($___lwDataTable->appliedFiltersData()) > 0)
                            <span @class($___lwDataTable->preset()->get('applied-filters.label.class'))>
                                @lang('erickcomp_lw_data_table::messages.active_filters_label'):
                            </span>
                        @endif
                        @if(!empty(\trim($search)))

                            <span @class($___lwDataTable->preset()->get('applied-filters.applied-filter-item.class'))>
                                {{-- <button wire:click="clearSearch()">x</button> --}}
                                <button
                                    x-on:click="clearSearch()"
                                    @class($___lwDataTable->preset()->get('applied-filters.button-remove-applied-filter-item.class'))
                                    >
                                    {!! $___lwDataTable->preset()->get('applied-filters.button-remove-applied-filter-item.content') !!}
                                </button>
                                @lang('erickcomp_lw_data_table::messages.applied_search_label'): "{{ $search }}"
                            </span>

                        @endif

                        @foreach ($___lwDataTable->appliedFiltersData() as $appliedFilterData)
                            <span @class($___lwDataTable->preset()->get('applied-filters.applied-filter-item.class'))>
                                {{-- <button wire:click="removeFilter('{{ $appliedFilterData['wire-name'] }}')">x</button></button>
                                --}}
                                <button
                                    x-on:click="removeFilter('{{ Str::chopStart($appliedFilterData['wire-name'], "{$this->filtersUrlParam()}.") }}')"
                                    @class($___lwDataTable->preset()->get('applied-filters.button-remove-applied-filter-item.class'))>
                                    {!! $___lwDataTable->preset()->get('applied-filters.button-remove-applied-filter-item.content') !!}
                                </button>
                                {{ $appliedFilterData['label'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div> <!-- end: lw-dt-table-actions -->
    @endif

    {{--
    @foreach($dataTable->actionsRows as $actionsRow)
    <div class="lw-dt-table-actions-row">
        @if($actionsRow->hasCustomRenderer())
        @php
        $actionsRowViewData = [
        '__dataTable' => $dataTable,
        '___lwDataTable' => $___lwDataTable,
        ];
        @endphp
        {!! Blade::render($actionsRow->customRendererCode, $actionsRowViewData) !!}
        @else
        {!! $actionsRow->render() !!}
        @endif
    </div>
    @endforeach
    --}}

    <table {{ $dataTable->tableAttributes->class($___lwDataTable->preset()->get('table.class')) }}>
        <thead {{ $dataTable->theadAttributes->class($___lwDataTable->preset()->get('table.thead.class')) }}>
            <tr {{ $dataTable->theadTrAttributes->class($___lwDataTable->preset()->get('table.thead.tr.class')) }}>
                @foreach ($dataTable->columns as $column)
                    @php
                        $thAttributes = $column->thAttributes->merge($dataTable->thAttributes->all());
                        //$thAttributes = $dataTable->thAttributes->merge($column->thAttributes->all());

                        if ($column->isSortable() && count($rows) > 1) {
                            $thAttributes['wire:click'] = "setSortBy('{$column->dataField}')";
                        }
                    @endphp
                    <th {{ $thAttributes->class($___lwDataTable->preset()->get('table.thead.tr.th.class')) }}>
                        {{ $column->title }}
                        @if ($column->isSortable() && count($rows) > 1 && $___lwDataTable->preset()->get('table.thead.tr.th.sorting.show-indicators'))
                            @php
                                $lowercaseSortDir = $column->dataField === $sortBy
                                    ? \strtolower(empty($sortDir) ? 'none' : $sortDir)
                                    : 'none';

                                $sortingPresetKey = "table.thead.tr.th.sorting.indicator-$lowercaseSortDir-class";
                            @endphp
                            <span @class($___lwDataTable->preset()->get($sortingPresetKey))></span>
                        {{--
                            @php
                                $columnSortClass = $column->dataField === $sortBy
                                    ? Str::kebab("{$dataTable->sortingClassPrefix}-" . \strtolower(empty($sortDir) ? 'none' : $sortDir))
                                    : "{$dataTable->sortingClassPrefix}-none";
                            @endphp
                            <span class="{{$dataTable->sortingClassPrefix}} {{ $columnSortClass }}"></span>
                        --}}
                        @endif
                        
                    </th>
                @endforeach
            </tr>
            
            @if($dataTable->hasSearchableColumns() && (count($rows) > 0 || !empty(\array_filter($columnsSearch))))
                <tr {{ $dataTable->theadSearchTrAttributes->class($___lwDataTable->preset()->get('table.thead.tr.search.class')) }}>
                    @foreach ($dataTable->columns as $column)
                        <th {{ $dataTable->theadSearchThAttributes->class($___lwDataTable->preset()->get('table.thead.tr.search.th.class')) }}>
                            @if ($column->isSearchable())
                                <input
                                    type="text"
                                    wire:model.live.debounce.{{ $___lwDataTable->preset()->get('table.thead.tr.search.debounce-ms') }}ms="columnsSearch.{{ $column->dataField }}"
                                    {{ $column->thSearchInputAttributes->class($___lwDataTable->preset()->get('table.thead.tr.search.th.input.class')) }}
                                    />
                            @endif
                        </th>
                    @endforeach
                </tr>
            @endif
        </thead>
        <tbody {{$dataTable->tbodyAttributes->class($___lwDataTable->preset()->get('table.tbody.class')) }}>
            
            @forelse ($rows as $row)
                @php
                    $trAttributes = new ComponentAttributeBag();
                    $trAttributesModifierCode = $dataTable->getTrAttributesModifierCode();
                @endphp
                @if(!empty($trAttributesModifierCode))
                    @php
                        $trAttributes = clone $dataTable->tbodyTrAttributes;

                        $modifierViewData = [
                            '__dataTable' => $dataTable,
                            '__rowData' => $row,
                            '__trAttributes' => $trAttributes,
                            'loop' => $loop,
                        ];
                    @endphp
                    {!! Blade::render($trAttributesModifierCode, $modifierViewData) !!}
                @else
                    @php
                        $trAttributes = $dataTable->tbodyTrAttributes->class($___lwDataTable->preset()->get('table.tbody.tr.class'));
                    @endphp
                @endif

                <tr {{ $trAttributes }} wire:key="{{ $row->{$dataTable->dataIdentityColumn} }}">
                    @foreach ($dataTable->columns as $column)
                        @php
                            $customRenderedColumn = '';
                        @endphp
                        @if($column instanceof CustomRenderedColumn)
                            @php
                                $customRenderedColumn = Blade::render($column->customRendererCode, ['loop' => $loop->parent, '__rowData' => $row]);
                                $trimmed = $customRenderedColumn;
                            @endphp

                            @if (preg_match('/^<\s*td\s*.*>.*<\/\s*td\s*>$/is', $trimmed))
                                {!! $customRenderedColumn !!}
                            @else
                                <td {{ $column->tdAttributes?->class($___lwDataTable->preset()->get('table.tbody.tr.td.class')) }}>
                                    {!! $customRenderedColumn !!}
                                </td>
                            @endif

                            @continue
                        @endif

                        <td {{ $column->tdAttributes?->class($___lwDataTable->preset()->get('table.tbody.tr.td.class')) }} >
                            @if (!empty($customRenderedColumn))
                                {!! $customRenderedColumn !!}
                            @elseif($column instanceof DataColumn)
                                {{ $row->{$column->dataField} }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr {{ $dataTable->tbodyTrAttributes->class($___lwDataTable->preset()->get('table.tbody.tr.nodatafound.class')) }}>
                    <td class="lw-dt-nodatafound-td" colspan="{{ max([count($dataTable->columns), 1]) }}">
                        @lang('erickcomp_lw_data_table::messages.no_data_found_table_td_text')
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($dataTable->hasFooter())
            @php
                $rendered = Blade::render($dataTable->footer->rendererCode, ['___lwDataTable' => $___lwDataTable, 'rows' => $rows]);
                $trimmed = \trim($rendered);
            @endphp

            @if (preg_match('/^<\s*tfoot\s*.*>.*<\/\s*tfoot\s*>$/is', $trimmed))
                {!! $rendered !!}
            @else
                <tfoot {{ $dataTable->footer->attributes->class($___lwDataTable->preset()->get('table.tfoot.class')) }}>
                    {!! $rendered !!}
                </tfoot>
            @endif

            @php
                unset($rendered, $trimmed);
            @endphp
        @endif
    </table>

    @if (\is_object($rows) && \method_exists($rows, 'links'))
        {{-- @TODO: Create params to choose between pagination styles --}}
        @if($dataTable->paginationCode != null)
            $paginationVars = [
            '__dataTable' => $dataTable,
            '__rows' => $rows
            ];
            {!! Blade::render($dataTable->paginationCode, $paginationVars) !!}
        @else
            <div @class(['lw-dt-pagination-container'])>
                {{ $rows->links() }}
            </div>
        @endif
    @endif
</div>

@assets

@foreach ($___lwDataTable->preset()->get('assets') as $asset)
    {!! $asset !!}
@endforeach

@foreach ($dataTable->assets as $asset)
    {!! $asset !!}
@endforeach

@endassets

@script

<script>
    Alpine.store(
        '{{ $___lwDataTable->getId() }}',
        Alpine.reactive({
            inputSearch: {{ Illuminate\Support\Js::from($search) }},
            inputFilters: {{ Illuminate\Support\Js::from($___lwDataTable->computeInitialFilters()) }},

            applySearch(wireHandler) {
                wireHandler.set('search', this.inputSearch);
            },

            applyFilters(wireHandler) {
                wireHandler.applyFilters(this.inputFilters);
            },

            clearSearch(wireHandler) {
                this.inputSearch = '';
                this.applySearch(wireHandler);
            },

            removeFilter(wireHandler, filter) {
                if (typeof filter !== 'string') {
                    return;
                }

                const keys = filter.split('.');
                const lastKey = keys.pop();
                const parent = keys.reduce((acc, key) => acc?.[key], this.inputFilters);
                const parentItem = parent[lastKey];
                const parentItemIsObject = typeof parent[lastKey] === 'object';

                const hasFrom = parentItemIsObject && 'from' in parentItem;
                const hasTo = parentItemIsObject && 'to' in parentItem;

                if (!hasFrom && !hasTo) {
                    parent[lastKey] = '';
                } else {
                    if (hasFrom) {
                        parent[lastKey]['from'] = '';
                    }

                    if (hasTo) {
                        parent[lastKey]['to'] = '';
                    }
                }

                this.applyFilters(wireHandler);
            }
        })
    );
</script>

@foreach ($___lwDataTable->preset()->get('scripts') as $script)
    {!! $script !!}
@endforeach

@foreach ($dataTable->scripts as $script)
    {!! $script !!}
@endforeach
@endscript
