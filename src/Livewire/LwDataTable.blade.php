<?php

use ErickComp\LivewireDataTable\DataTable\CustomRenderedColumn;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

/** @var \ErickComp\LivewireDataTable\DataTable $this->dataTable */
/** @var \ErickComp\LivewireDataTable\Livewire\LwDataTable $this */
/** @var \ErickComp\LivewireDataTable\DataTable\Filter $filterItem */
/** @var LengthAwarePaginator|Paginator|CursorPaginator|Collection|LazyCollection $rows */

$thAttributes = function ($columnThAttributes, $tableThAttributes): ComponentAttributeBag {
    return $columnThAttributes->merge($tableThAttributes->all());
};

?>
{{-- @debugger --}}
<div {{ $this->dataTable->containerAttributes->class([...$this->preset()->get('main-container.class'), 'lw-dt']) }}
    x-data="{!! $this->xData() !!}">
    @if($this->dataTable->hasTableActions())
        <div @class($this->preset()->get('actions.container.class'))>
            <div @class($this->preset()->get('actions.row.class'))>
                @if($this->dataTable->isSearchable())
                    <div {{ $this->dataTable->search->componentAttributes->class($this->preset()->get('search.container.class', [])) }}>
                        @if ($this->dataTable->search->hasCustomRenderer()))
                            @php
                                $searchViewData = [
                                    //'__dataTable' => $this->dataTable,
                                    '___lwDataTable' => $this,
                                ];
                            @endphp
                            {!! Blade::render($this->dataTable->search->customRendererCode, $searchViewData) !!}
                            @php unset($searchViewData); @endphp
                        @else
                            <input {{ $this->dataTable->search->inputAttributes->class($this->preset()->get('search.input.class', [])) }} />

                            <button {{ $this->dataTable->search->buttonAttributes->class($this->preset()->get('search.button.class', [])) }}
                                x-on:click="applySearch()" >
                                @php
                                $shouldUseIconInSearchButton = $this->dataTable->search->shouldShowIconOnApplyButton();
                                $iconSearchButtonPosition = $this->preset()->get('search.button.icon-position','none');
                                @endphp

                                @if($shouldUseIconInSearchButton && $iconSearchButtonPosition === 'left')
                                    {!! $this->preset()->get('search.button.icon', '') !!}
                                @endif

                                {{ __('erickcomp_lw_data_table::messages.search_button_label') }}

                                @if($shouldUseIconInSearchButton && $iconSearchButtonPosition === 'right')
                                    {!! $this->preset()->get('search.button.icon', '') !!}
                                @endif
                            </button>
                        @endif
                    </div>
                @endif

                @if($this->dataTable->isFilterable() && $this->dataTable->filters->isCollapsible())

                    <button {{ $this->dataTable->filters->getToggleButtonAttributes($this->preset(), $this->shouldShowFiltersContainer()) }}
                        x-bind:class="{ 'active': filtersContainerIsOpen }"
                        x-on:click="toggleFiltersContainer()"
                         >

                        @php
                        $shouldUseIconInToggleFilterButton = $this->dataTable->filters->shouldShowIconOnToggleButton();
                        $iconToggleFiltersButtonPosition = $this->preset()->get('filters.toggle-button.icon-position','none');
                        @endphp

                        @if($shouldUseIconInToggleFilterButton && $iconToggleFiltersButtonPosition === 'left')
                            {!! $this->preset()->get('filters.toggle-button.icon', '') !!}
                        @endif
                        
                        {{ __('erickcomp_lw_data_table::messages.toggle_filters_button_label') }}

                        @if($shouldUseIconInToggleFilterButton && $iconToggleFiltersButtonPosition === 'right')
                            {!! $this->preset()->get('filters.toggle-button.icon', '') !!}
                        @endif
                    </button>
                @endif
            </div> <!-- end: lw-dt-table-actions-row -->
            @if($this->dataTable->isFilterable())
                <div @class($this->preset()->get('actions.row.class', [])) {{-- @style(['display: none' => !$this->shouldShowFiltersContainer() && false]) --}}>
                    <div {{ $this->dataTable->filters->containerAttributes($this->preset()) }}>
                        @if(!$this->dataTable->filters->isCollapsible())
                            <span @class($this->preset()->get('filters.title.class', []))>
                                @if($this->preset()->get('filters.title.icon-position') === 'left')
                                    {!! $this->preset()->get('filters.title.icon', '') !!}
                                @endif
                                {{ $this->dataTable->filters->title() }}
                                @if($this->preset()->get('filters.title.icon-position') === 'rigth')
                                    {!! $this->preset()->get('filters.title.icon', '') !!}
                                @endif
                            </span>
                        @endif
                        @php $renderedFilterItemsNames = []; @endphp
                        @foreach($this->dataTable->filters->filtersItems as $filterItem)
                            <div @class($this->preset()->get('filters.item.class', []))>
                                <div @class([
                                    ...$this->preset()->get('filters.item.content.class', []) ,
                                    ...($filterItem->mode === Filter::MODE_RANGE ? $this->preset()->get('filters.item.content.range.class', []) : [])
                                    ])>
                                    @if(!empty($filterItem->customRendererCode))
                                        {!! $filterItem->getCustomRendererCodeWithXModel('inputFilters', ['___lwDataTable' => $this]) !!}
                                    @else
                                        @php
                                            if (\in_array($filterItem->attributes['name'], $renderedFilterItemsNames)) {
                                                throw new \LogicException("Each filter item must have a unique name. Duplicated name found: [{$filterItem->attributes['name']}]");
                                            }

                                            $renderedFilterItemsNames[] = $filterItem->attributes['name'];
                                        @endphp

                                        <legend @class($this->preset()->get('filters.item.content.legend.class', []))>
                                            {{-- ilterItem-?label . ?({$filterItem-?inputType})? --}}
                                            <span @class($this->preset()->get('filters.item.content.legend.span.class', []))>
                                                {{-- ilterItem-?label . ?({$filterItem-?inputType})? --}}
                                                {{ $filterItem->label }}
                                            </span>
                                        </legend>

                                        @if(\in_array($filterItem, [Filter::TYPE_SELECT, Filter::TYPE_SELECT_MULTIPLE], true))
                                            <select {{ $filterItem->inputAttributes(except: 'name')->class($this->preset()->get('filters.item.content.select.class')) }}
                                                name="{{ $filterItem->buildInputNameAttribute($this->filtersUrlParam()) }}"
                                                {{-- wire:model="{{ $filterItem->buildWireModelAttribute('inputFilters') }}" --}}
                                                {{-- x-model="{{ $filterItem->buildWireModelAttribute('inputFilters') }}"> --}}
                                                {{-- x-on:input="updateFilterInput('{{ $filterItem->buildWireModelAttribute('inputFilters')
                                                }}')" --}}
                                                x-model="{{ $filterItem->buildXModelAttribute('inputFilters') }}">
                                                @foreach($filterItem->getSelectOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            @if($filterItem->mode === Filter::MODE_RANGE)
                                                @php
                                                    $inputFromClasses = new ComponentAttributeBag(
                                                    [ 'class' => Arr::toCssClasses($this->preset()->get("filters.item.content.input-{$filterItem->htmlInputType()}.class", []))]
                                                    )->class($this->preset()->get("filters.item.content.range.input.from.class", []));

                                                    $inputToClasses = new ComponentAttributeBag(
                                                    [ 'class' => Arr::toCssClasses($this->preset()->get("filters.item.content.input-{$filterItem->htmlInputType()}.class", []))]
                                                    )->class($this->preset()->get("filters.item.content.range.input.to.class", []));
                                                @endphp
                                                <span @class($this->preset()->get("filters.item.content.range.label.from.class", ''))>
                                                    {{ __('erickcomp_lw_data_table::messages.range_filter_label_from') }}:
                                                </span>
                                                <input
                                                    type="{{ $filterItem->htmlInputType() }}"
                                                    {{ $filterItem->inputAttributes(except: 'name', range: 'from')->class($inputFromClasses['class']) }}
                                                    name="{{ $filterItem->buildInputNameAttribute($this->filtersUrlParam(), 'from') }}"
                                                    {{-- wire:model="{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}" --}}
                                                    {{-- x-model="{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}" --}}
                                                    {{--
                                                    x-on:input="updateFilterInput('{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}')"
                                                    --}}
                                                    x-model="{{ $filterItem->buildXModelAttribute('inputFilters', 'from') }}"
                                                    x-on:keydown.enter="applyFilters()"
                                                    value="{{ $this->getFilterValue($filterItem)['from'] ?? '' }}">
                                                <span @class($this->preset()->get("filters.item.content.range.label.to.class", ''))>
                                                    {{ __('erickcomp_lw_data_table::messages.range_filter_label_to') }}:
                                                </span>
                                                <input
                                                    type="{{ $filterItem->htmlInputType() }}"
                                                    {{ $filterItem->inputAttributes(except: 'name', range: 'to')->class($inputToClasses['class']) }}
                                                    name="{{ $filterItem->buildInputNameAttribute($this->filtersUrlParam(), 'to') }}"
                                                    {{-- wire:model="{{ $filterItem->buildWireModelAttribute('inputFilters', 'to') }}" --}}
                                                    {{-- x-model="{{ $filterItem->buildWireModelAttribute('inputFilters', 'to') }}" --}}
                                                    {{--
                                                    x-on:input="updateFilterInput('{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}')"
                                                    --}}
                                                    x-model="{{ $filterItem->buildXModelAttribute('inputFilters', 'to') }}"
                                                    x-on:keydown.enter="applyFilters()"
                                                    value="{{ $this->getFilterValue($filterItem)['to'] ?? '' }}">
                                            @else
                                                <input
                                                    type="{{ $filterItem->htmlInputType() }}"
                                                    {{ $filterItem->inputAttributes(except: 'name')->class($this->preset()->get("filters.item.content.input-{$filterItem->inputType}.class", '')) }}
                                                    name="{{ $filterItem->buildInputNameAttribute($this->filtersUrlParam()) }}"
                                                    {{-- wire:model="{{ $filterItem->buildWireModelAttribute('inputFilters') }}" --}}
                                                    {{-- x-model="{{ $filterItem->buildWireModelAttribute('inputFilters') }}" --}}
                                                    {{-- x-on:input="updateFilterInput('{{ $filterItem->buildWireModelAttribute('inputFilters') }}')" --}}
                                                    {{-- wire:model="{{ $filterItem->buildWireModelAttribute('inputFilters') }}" --}}
                                                    {{-- x-model="{{ $filterItem->buildWireModelAttribute('inputFilters') }}" --}}
                                                    {{--
                                                    x-on:input="updateFilterInput('{{ $filterItem->buildWireModelAttribute('inputFilters') }}')"
                                                    --}}
                                                    x-model="{{ $filterItem->buildXModelAttribute('inputFilters') }}"
                                                    x-on:keydown.enter="applyFilters()"
                                                    value="{{ $this->getFilterValue($filterItem) ?? '' }}">
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <div @class($this->preset()->get('filters.apply-button.container.class'))>
                            {{-- <button wire:click="applyFilters()" {{ $this->dataTable->filters->buttonApplyAttributes }}> --}}
                            <button {{--  x-bind:disabled="!changedFilters" --}} x-on:click="applyFilters()" {{ $this->dataTable->filters->buttonApplyAttributes->class($this->preset()->get('filters.apply-button.class')) }}>
                                {{ __('erickcomp_lw_data_table::messages.apply_filters_button_label') }}
                            </button>
                        </div>
                    </div> {{-- end: $this->dataTable->filters->containerAttributes --}}

                </div>
            @endif

            @if (!empty($search) || (!empty($this->dataTable->filters) && !empty($this->appliedFiltersData())))
                <div @class($this->preset()->get('actions.row.class'))>
                    <div @class($this->preset()->get('applied-filters.container.class')) >
                        @if(count($this->appliedFiltersData()) > 0 || !empty($search))
                            <span @class($this->preset()->get('applied-filters.label.class'))>
                                {{ __('erickcomp_lw_data_table::messages.active_filters_label') }}:
                            </span>
                        @endif

                        @php
                            $removeFilterButtonPosition = $this->preset()->get('applied-filters.applied-filter-item.position', 'right');
                        @endphp

                        @if(!empty(\trim($search)))
                            <span @class($this->preset()->get('applied-filters.applied-filter-item.class', []))>
                                @if ($removeFilterButtonPosition === 'right')
                                    <span @class($this->preset()->get('applied-filters.applied-filter-item.label-class', []))>
                                        {{ __('erickcomp_lw_data_table::messages.applied_search_label') }}: "{{ $search }}"
                                    </span>
                                @endif
                                
                                <button
                                    x-on:click="clearSearch()"
                                    @class($this->preset()->get('applied-filters.button-remove-applied-filter-item.class', []))
                                    >
                                    {!! $this->preset()->get('applied-filters.button-remove-applied-filter-item.content', '') !!}
                                </button>
                                
                                @if ($removeFilterButtonPosition === 'left')
                                    <span @class($this->preset()->get('applied-filters.applied-filter-item.label-class', []))>
                                        {{ __('erickcomp_lw_data_table::messages.applied_search_label') }}: "{{ $search }}"
                                    </span>
                                @endif
                            </span>
                        @endif
                        
                        @foreach ($this->appliedFiltersData() as $appliedFilterData)
                            <span @class($this->preset()->get('applied-filters.applied-filter-item.class', []))>
                                {{-- <button wire:click="removeFilter('{{ $appliedFilterData['wire-name'] }}')">x</button></button>
                                --}}

                                @if ($removeFilterButtonPosition === 'right')
                                    <span @class($this->preset()->get('applied-filters.applied-filter-item.label-class', []))>
                                        {{ $appliedFilterData['label'] }}
                                    </span>
                                @endif
                                
                                <button
                                    {{-- 
                                    x-on:click="removeFilter('{{ Str::chopStart($appliedFilterData['wire-name'], "{$this->filtersUrlParam()}.") }}')"
                                    --}}
                                    x-on:click="removeFilter('{{ Str::chopStart($appliedFilterData['removal-key'], "{$this->filtersUrlParam()}") }}')"
                                    @class($this->preset()->get('applied-filters.button-remove-applied-filter-item.class', []))>
                                    {!! $this->preset()->get('applied-filters.button-remove-applied-filter-item.content') !!}
                                </button>

                                @if ($removeFilterButtonPosition === 'left')
                                    <span @class($this->preset()->get('applied-filters.applied-filter-item.label-class', []))>
                                        {{ $appliedFilterData['label'] }}
                                    </span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($this->dataTable->hasBulkActions() || count($this->dataTable->perPageOptions) > 1)
                <div @class($this->preset()->get('actions.bulk-actions-and-per-page.container.class', []))>
                    @if ($this->dataTable->hasBulkActions() && false)
                        <select @class($this->preset()->get('actions.bulk-actions-and-per-page.bulk-actions-select.class', []))>
                            @foreach(['' => __('erickcomp_lw_data_table::messages.bulk_actions_label') , 1 => 'mock bulk action 1', 2 => 'mock bulk action 2'] as $bulkAction => $bulkActionLabel)
                                <option value="{{ $bulkAction }}">{{ $bulkActionLabel }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if(count($this->dataTable->perPageOptions) > 1)
                        <div @class($this->preset()->get('actions.bulk-actions-and-per-page.per-page.container.class', []))>

                            @if($this->preset()->get('actions.bulk-actions-and-per-page.per-page.label.position', 'after') === 'before')
                                <span @class($this->preset()->get('actions.bulk-actions-and-per-page.per-page.label.class', []))>
                                    {{ __('erickcomp_lw_data_table::messages.per_page_label') }}
                                </span>
                            @endif
                            
                            <select @class($this->preset()->get('actions.bulk-actions-and-per-page.per-page.select.class', []))
                                wire:model.live="perPage">
                                @foreach($this->dataTable->perPageOptionsForSelect() as $perPageOptionVal => $perPageOptionLabel)
                                    <option value="{{ $perPageOptionVal }}">{{ $perPageOptionLabel }}</option>
                                @endforeach
                            </select>

                            @if ($this->preset()->get('actions.bulk-actions-and-per-page.per-page.label.position', 'after') === 'after')
                                <span @class($this->preset()->get('actions.bulk-actions-and-per-page.per-page.label.class', []))>
                                    {{ __('erickcomp_lw_data_table::messages.per_page_label') }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div> <!-- end: lw-dt-table-actions -->
    @endif

    {{--
    @foreach($this->dataTable->actionsRows as $actionsRow)
    <div class="lw-dt-table-actions-row">
        @if($actionsRow->hasCustomRenderer())
        @php
        $actionsRowViewData = [
        '__dataTable' => $this->dataTable,
        '___lwDataTable' => $this,
        ];
        @endphp
        {!! Blade::render($actionsRow->customRendererCode, $actionsRowViewData) !!}
        @else
        {!! $actionsRow->render() !!}
        @endif
    </div>
    @endforeach
    --}}

    @php
        $shouldAllowColumnsSearch = $this->shouldAllowColumnsSearch($rows);
        $shouldAllowSorting = $this->shouldAllowSorting($rows);
    @endphp
    <table {{ $this->dataTable->tableAttributes->class($this->preset()->get('table.class')) }}>
        <thead {{ $this->dataTable->theadAttributes->class($this->preset()->get('table.thead.class')) }}>
            <tr {{ $this->dataTable->theadTrAttributes->class($this->preset()->get('table.thead.tr.class')) }}>
                @foreach ($this->dataTable->columns as $column)
                    <th {{ $column->buildThAttributes($this->preset()->get('table.thead.tr.th.class'), $shouldAllowSorting) }}>
                        {{ $column->title }}

                        @if ($shouldAllowSorting && $column->isSortable() && $this->preset()->get('table.thead.tr.th.sorting.show-indicators'))
                            @php
                                $lowercaseSortDir = $column->dataField === $sortBy
                                    ? \strtolower(empty($sortDir) ? 'none' : $sortDir)
                                    : 'none';

                                $sortingHtml = $this->preset()->get("table.thead.tr.th.sorting.indicator-$lowercaseSortDir");
                            @endphp
                            {!! $sortingHtml !!}
                        @endif
                        
                    </th>
                @endforeach
            </tr>
            
            @if($this->dataTable->hasSearchableColumns() && $this->hasRows($rows) || !empty(\array_filter($columnsSearch))))
                <tr {{ $this->dataTable->theadSearchTrAttributes->class($this->preset()->get('table.thead.tr.search.class')) }}>
                    @foreach ($this->dataTable->columns as $column)
                        <th {{ $this->dataTable->theadSearchThAttributes->class($this->preset()->get('table.thead.tr.search.th.class')) }}>
                            @if ($column->isSearchable())
                                <input
                                    type="text"
                                    wire:model.live.debounce.{{ $this->preset()->get('table.thead.tr.search.debounce-ms') }}ms="columnsSearch.{{ $column->dataField }}"
                                    {{ $column->thSearchInputAttributes->class($this->preset()->get('table.thead.tr.search.th.input.class')) }}
                                    value="{{ Arr::get($this->columnsSearch, $column->dataField, '') }}"
                                    />
                            @endif
                        </th>
                    @endforeach
                </tr>
            @endif
        </thead>
        <tbody {{$this->dataTable->tbodyAttributes->class($this->preset()->get('table.tbody.class')) }}>
            @php
                $noData = new \stdClass();
            @endphp
            @debugger
            @forelse ($rows as $row)
                <tr {{ $this->dataTable->getTrAttributesForRow($this, $row, $loop) }} wire:key="{{ \data_get($row, $this->dataTable->dataIdentityColumn) }}">
                    @foreach ($this->dataTable->columns as $column)
                        @php
                            $tdAttributes = $column->buildTdAttributes($this->preset()->get('table.tbody.tr.td.class'));
                        @endphp
                        
                        @if($column instanceof CustomRenderedColumn)
                            @php
                                $customRenderedColumn = Blade::render($column->customRendererCode, ['attributes' => $tdAttributes,'loop' => $loop->parent, '__row' => $row]);
                                $trimmed = trim($customRenderedColumn);
                            @endphp

                            @if (\preg_match('/^<\s*td\s*.*>.*<\/\s*td\s*>$/is', $trimmed))
                                {!! $customRenderedColumn !!}
                            @else
                                <td {{ $tdAttributes }} >
                                    {!! $customRenderedColumn !!}
                                </td>
                            @endif

                            @continue
                        @elseif ($column instanceof DataColumn)
                            <td {{ $tdAttributes }}>
                                @php
                                $cellContent = \data_get($row, $column->dataField, $noData);
                                
                                if($cellContent === $noData) {
                                    throw new \LogicException("Cannot get data for column [{$column->dataField}] on row #{$loop->iteration}");
                                }
                                @endphp
                                
                                {{ $cellContent }}
                            </td>
                        @else
                            @php throw new \InvalidArgumentException('Cannot render column of type ' . \get_debug_type($column)); @endphp
                        @endif
                    @endforeach
                </tr>
                @php
                    if ($rows instanceof LazyCollection) {
                        \file_put_contents(\storage_path('logs/data-table-lazy.log'), ($loop->iteration . PHP_EOL), FILE_APPEND);
                        unset($row);
                    }
                @endphp
            @empty
                <tr {{ $this->dataTable->tbodyTrAttributes->class($this->preset()->get('table.tbody.tr.nodatafound.class')) }}>
                    <td class="lw-dt-nodatafound-td" colspan="{{ max([count($this->dataTable->columns), 1]) }}">
                        {{ __('erickcomp_lw_data_table::messages.no_data_found_table_td_text')  }}
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($this->dataTable->hasFooter())
            @php
                $rendered = Blade::render($this->dataTable->footer->rendererCode, ['___lwDataTable' => $this, 'rows' => $rows]);
                $trimmed = \trim($rendered);
            @endphp

            @if (preg_match('/^<\s*tfoot\s*.*>.*<\/\s*tfoot\s*>$/is', $trimmed))
                {!! $rendered !!}
            @else
                <tfoot {{ $this->dataTable->footer->attributes->class($this->preset()->get('table.tfoot.class')) }}>
                    {!! $rendered !!}
                </tfoot>
            @endif

            @php
                unset($rendered, $trimmed);
            @endphp
        @endif
    </table>

    @if($this->dataTable->paginationCode !== null)    
        {!! $this->renderCustomPagination($rows) !!}
    @else
        <div @class($this->preset()->get('pagination.container.class', [])))>
            {{ $this->renderPagination($rows) }}
        </div>
    @endif

    {{-- 
    @if (\is_object($rows) && \method_exists($rows, 'links'))
        @if($this->dataTable->paginationCode != null)
            $paginationVars = [
            '__dataTable' => $this->dataTable,
            '__rows' => $rows
            ];
            {!! Blade::render($this->dataTable->paginationCode, $paginationVars) !!}
        @else
            <div @class(['lw-dt-pagination-container'])>
                {{ $rows->render() }}
            </div>
        @endif
    @endif
    --}}
    

    @if(!empty($this->preset()->get('loader-overlay.html', null)))
        {!! $this->preset()->get('loader-overlay.html') !!}
    @endif


</div>

@assets

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

@foreach ($this->preset()->get('loader-overlay.assets', []) as $asset)
    {!! $asset !!}
@endforeach

@foreach ($this->preset()->get('assets', []) as $asset)
    {!! $asset !!}
@endforeach

@foreach ($this->dataTable->assets as $asset)
    {!! $asset !!}
@endforeach

<script>
    @php
        $reloadAlertConfig = $this->preset()->get('reload-alert', null);

        $reloadAlertConfig['alert-before-reload'] ??= true;

        if ($reloadAlertConfig['alert-before-reload'] === true) {
            if (($reloadAlertConfig['function-name'] ?? null) === null) {
                $reloadAlertConfig['function-name'] = 'lwDataTableReloadAlert';
                $reloadAlertConfig['function-code'] = <<<JS
                    async function lwDataTableReloadAlert(message, callback) {
                        alert(message);
                        callback();
                    }
                JS;
            }

            $reloadAlertConfig['function-code'] ??= <<<JS
                async function {$reloadAlertConfig['function-name']}(message, callback) {
                    alert(message);
                    callback();
                }
            JS;

            if (($reloadAlertConfig['alert-before-reload'] ?? true) === true) {
                echo $reloadAlertConfig['function-code'];
                //$message = __('erickcomp_lw_data_table::messages.reload_required');
            }
        }
    @endphp
</script>
@endassets

@script

<script>
    Alpine.store(
        '{{ $this->getId() }}',
        Alpine.reactive({
            filtersContainerIsOpen: {!! $this->shouldShowFiltersContainer() ? 'true' : 'false' !!},
            inputSearch: {{ Illuminate\Support\Js::from($search) }},
            inputFilters: {{ Illuminate\Support\Js::from($this->computeInitialFilters()) }},

            toggleFiltersContainer(wireHandler) {
                this.filtersContainerIsOpen = !this.filtersContainerIsOpen;
                wireHandler.filtersContainerIsOpen = this.filtersContainerIsOpen;
            },

            changedSearchTerms(wireHandler) {
                return wireHandler.get('search') !== this.inputSearch;
            },

            changedFilters(wireHandler) {
                return JSON.stringify(wireHandler.get('rawFilters')) !== JSON.stringify(this.inputFilters);
            },

            applySearch(wireHandler) {
                if (!this.changedSearchTerms(wireHandler)) { {{-- No changes, no server roundtrip --}}
                    return;
                }

                wireHandler.set('search', this.inputSearch);
            },

            applyFilters(wireHandler) {
                if (!this.changedFilters(wireHandler)) { {{-- No changes in filters, no server roundtrip needed --}}
                    return;
                }

                wireHandler.applyFilters(this.inputFilters);
            },

            clearSearch(wireHandler) {
                if (this.inputSearch === '')  { {{-- Already empty, no server roundtrip needed --}}
                    return;
                }

                this.inputSearch = '';
                this.applySearch(wireHandler);
            },

            removeFilter(wireHandler, filter) {
                if (typeof filter !== 'string') {
                    return;
                }

                {{--
                // Old approach (dot notation)
                const keys = filter.split('.');
                --}}

                const keys = [...filter.matchAll(/\[([^\]]+)\]/g)].map(m => m[1]);
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

    {{--
    debugger;
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                debugger;
                if (status === 419) {
                    //confirm('Your custom page expiration behavior...')
                    @if ($reloadAlertConfig === null || ($reloadAlertConfig['alert-before-reload'] ?? true) === true)
                        @php
                        $reloadRequiredmessage = 'Hue';
                        @endphp
                        {{ $reloadAlertConfig['function-name'] }}('{{ $reloadRequiredmessage }}', function () {window.location.reload();});
                    @else
                        //alert('sem alert. =P');
                        window.location.reload();
                    @endif
 
                    preventDefault();
                }
            });
        });
    });
    --}}

    {{-- 
    $wire.on('{{ $this::EVENT_RELOAD_REQUIRED }}', () => {
        @if ($reloadAlertConfig === null || ($reloadAlertConfig['alert-before-reload'] ?? true) === true)
            {{ $reloadAlertConfig['function-name'] }}('{{ $message }}', function () {window.location.reload();});
        @else
            alert('sem alert. =P');
            window.location.reload();
        @endif
        
    });
    --}}
    
</script>

@foreach ($this->preset()->get('scripts', []) as $script)
    {!! $script !!}
@endforeach

@foreach ($this->dataTable->scripts as $script)
    {!! $script !!}
@endforeach
@endscript
