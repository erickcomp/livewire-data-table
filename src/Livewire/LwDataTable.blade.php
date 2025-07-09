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
<div {{ $dataTable->containerAttributes->class(['lw-dt' => true]) }}
    x-data="{!! $___lwDataTable->xData() !!}">
    @if($dataTable->hasTableActions())
        <div class="lw-dt-table-actions">
            <div class="lw-dt-table-actions-row">
                @if($dataTable->isSearchable())
                    <div {{ $dataTable->search->componentAttributes }}>
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
                            <input {{ $dataTable->search->inputAttributes }} />

                            <button {{ $dataTable->search->buttonAttributes }}>
                                @if($dataTable->search->shouldRenderDefaultIconOnApplyButton())
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 512 512"
                                        width="14" height="14"
                                        fill="currentColor"
                                        style="vertical-align: middle;">
                                        <path
                                            d="M495 466.1l-110.1-110.1c31.1-37.7 48-84.6 48-134 0-56.4-21.9-109.3-61.8-149.2-39.8-39.9-92.8-61.8-149.1-61.8-56.3 0-109.3 21.9-149.2 61.8C33.1 112.7 11.2 165.7 11.2 222c0 56.3 21.9 109.3 61.8 149.2 39.8 39.8 92.8 61.8 149.2 61.8 49.5 0 96.4-16.9 134-48l110.1 110c8 8 20.9 8 28.9 0 8-8 8-20.9 0-28.9zM101.7 342.2c-32.2-32.1-49.9-74.8-49.9-120.2 0-45.4 17.7-88.2 49.8-120.3 32.1-32.1 74.8-49.8 120.3-49.8 45.4 0 88.2 17.7 120.3 49.8 32.1 32.1 49.8 74.8 49.8 120.3 0 45.4-17.7 88.2-49.8 120.3-32.1 32.1-74.9 49.8-120.3 49.8-45.4 0-88.1-17.7-120.2-49.9z" />
                                    </svg>
                                @endif
                                @lang('erickcomp_lw_data_table::messages.search_button_label')
                            </button>
                        @endif
                    </div>
                @endif

                @if($dataTable->isFilterable() && $dataTable->filters->isCollapsible())
                    <button {{ $dataTable->filters->buttonToggleAttributes->class(['active' => $___lwDataTable->shouldShowFiltersContainer()]) }}
                        x-bind:class="{ 'active': filtersContainerIsOpen }">

                        @if($dataTable->filters->shouldShowDefaultIconOnToggleButton())
                            <svg xmlns=" http://www.w3.org/2000/svg"
                                width="14" height="14" viewBox="0 0 24 24"
                                style="vertical-align: middle;">
                                <path d="M3 4h18l-7 10v5l-4 1v-6z" fill="currentColor" />
                            </svg>
                        @endif
                        @lang('erickcomp_lw_data_table::messages.toggle_filters_button_label')
                    </button>
                @endif
            </div> <!-- end: lw-dt-table-actions-row -->
            @if($dataTable->isFilterable())
                <div class="lw-dt-table-actions-row">
                    <div {{ $dataTable->filters->containerAttributes() }}>
                        @php $renderedFilterItemsNames = []; @endphp
                        @foreach($dataTable->filters->filtersItems as $filterItem)
                            <div class="filter-item">
                                <div @class(['filter-content', 'filter-range' => $filterItem->mode === Filter::MODE_RANGE])>
                                    @if(!empty($filterItem->customRendererCode))
                                        {!! $filterItem->getCustomRendererCodeWithXModel('inputFilters') !!}
                                    @else
                                        @php
                                            if (\in_array($filterItem->attributes['name'], $renderedFilterItemsNames)) {
                                                throw new \LogicException("Each filter item must have a unique name. Duplicated name found: [{$filterItem->attributes['name']}]");
                                            }

                                            $renderedFilterItemsNames[] = $filterItem->attributes['name'];
                                        @endphp

                                        <legend><span>{{ $filterItem->label }}</span></legend>

                                        @if(\in_array($filterItem, [Filter::TYPE_SELECT, Filter::TYPE_SELECT_MULTIPLE], true))
                                            <select {{ $filterItem->inputAttributes(except: 'name') }}
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
                                                <span>@lang('erickcomp_lw_data_table::messages.range_filter_label_from'):</span>
                                                <input
                                                    type="{{ $filterItem->htmlInputType() }}" {{ $filterItem->inputAttributes(except: 'name') }}
                                                    name="{{ $filterItem->buildInputNameAttribute($___lwDataTable->filtersUrlParam(), 'from') }}"
                                                    {{-- wire:model="{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}" --}}
                                                    {{-- x-model="{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}" --}}
                                                    {{--
                                                    x-on:input="updateFilterInput('{{ $filterItem->buildWireModelAttribute('inputFilters', 'from') }}')"
                                                    --}}
                                                    x-model="{{ $filterItem->buildXModelAttribute('inputFilters', 'from') }}"
                                                    x-on:keydown.enter="applyFilters()">
                                                <span>@lang('erickcomp_lw_data_table::messages.range_filter_label_to'):</span>
                                                <input
                                                    type="{{ $filterItem->htmlInputType() }}" {{ $filterItem->inputAttributes(except: 'name') }}
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
                                                    type="{{ $filterItem->htmlInputType() }}" {{ $filterItem->inputAttributes(except: 'name') }}
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
                        <div class="lw-dt-filter-apply-container">
                            <button {{ $dataTable->filters->buttonApplyAttributes }}>
                                @lang('erickcomp_lw_data_table::messages.apply_filters_button_label')
                            </button>
                        </div>
                    </div> {{-- end: $dataTable->filters->containerAttributes --}}

                </div>
            @endif

            @if (!empty($search) || (!empty($dataTable->filters) && !empty($___lwDataTable->appliedFiltersData())))
                <div class="lw-dt-table-actions-row">
                    <div>
                        @if(count($___lwDataTable->appliedFiltersData()) > 0)
                            <span style="padding: 0.25rem;">
                                @lang('erickcomp_lw_data_table::messages.active_filters_label'):
                            </span>
                        @endif
                        @if(!empty(\trim($search)))

                            <span style="padding: 0.25rem;font-weight: bold;">
                                {{-- <button wire:click="clearSearch()">x</button> --}}
                                <button x-on:click="clearSearch()">x</button>
                                @lang('erickcomp_lw_data_table::messages.applied_search_label'): "{{ $search }}"
                            </span>

                        @endif

                        @foreach ($___lwDataTable->appliedFiltersData() as $appliedFilterData)
                            <span style="padding: 0.25rem;font-weight: bold;">
                                {{-- <button wire:click="removeFilter('{{ $appliedFilterData['wire-name'] }}')">x</button></button>
                                --}}
                                <button
                                    x-on:click="removeFilter('{{ Str::chopStart($appliedFilterData['wire-name'], "{$this->filtersUrlParam()}.") }}')">x</button>
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

    <table {{ $dataTable->tableAttributes }}>
        <thead {{ $dataTable->theadAttributes }}>
            <tr {{ $dataTable->theadTrAttributes }}>
                @foreach ($dataTable->columns as $column)
                    @php
                        $thAttributes = $column->thAttributes->merge($dataTable->thAttributes->all());
                        //$thAttributes = $dataTable->thAttributes->merge($column->thAttributes->all());

                        if ($column->isSortable() && count($rows) > 0) {
                            $thAttributes['wire:click'] = "setSortBy('{$column->dataField}')";
                        }
                    @endphp
                    <th {{ $thAttributes }}>
                        {{ $column->title }}
                        @if ($column->isSortable() && count($rows) > 0)
                            @php
                                $columnSortClass = $column->dataField === $sortBy
                                    ? Str::kebab("{$dataTable->sortingClassPrefix}-" . \strtolower(empty($sortDir) ? 'none' : $sortDir))
                                    : "{$dataTable->sortingClassPrefix}-none";
                            @endphp
                            <span class="{{$dataTable->sortingClassPrefix}} {{ $columnSortClass }}"></span>
                        @endif
                    </th>
                @endforeach
            </tr>

            @if($dataTable->hasSearchableColumns() && (count($rows) > 0 || !empty(\array_filter($columnsSearch))))
                <tr {{ $dataTable->theadSearchTrAttributes }}>
                    @foreach ($dataTable->columns as $column)
                        <th {{ $dataTable->theadSearchThAttributes }}>
                            @if ($column->isSearchable())
                                <input type="text" wire:model.live.debounce.{{ $dataTable->columnsSearchDebounce }}ms="columnsSearch.{{ $column->dataField }}" />
                            @endif
                        </th>
                    @endforeach
                </tr>
            @endif
        </thead>
        <tbody {{$dataTable->tbodyAttributes }}>
            @debugger
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
                        $trAttributes = $dataTable->tbodyTrAttributes;
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
                                        <td {{ $column->tdAttributes ?? '' }}>
                                            {!! $customRenderedColumn !!}
                                        </td>
                                    @endif

                                    @continue
                        @endif

                                <td {{ $column->tdAttributes ?? '' }}>
                                    @if (!empty($customRenderedColumn))
                                        {!! $customRenderedColumn !!}
                                    @elseif($column instanceof DataColumn)
                                        {{ $row->{$column->dataField} }}
                                    @endif
                                </td>
                    @endforeach
                </tr>
            @empty
                <tr {{ $dataTable->tbodyTrAttributes->class(["lw-dt-nodatafound-tr"]) }}>
                    <td class="lw-dt-nodatafound-td" colspan="{{ max([count($dataTable->columns), 1]) }}">
                        @lang('erickcomp_lw_data_table::messages.no_data_found_table_td_text')
                    </td>
                </tr>
            @endforelse

        </tbody>
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
            <div @class(['lw-dt-pagination-container', 'default-pagination-style' => $dataTable->isUsingDefaultPaginationViews()])>
                {{ $rows->links() }}
            </div>
        @endif
    @endif
</div>

@assets

@foreach ($___lwDataTable->preset()->assets as $asset)
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

@foreach ($___lwDataTable->preset()->scripts as $script)
    {!! $script !!}
@endforeach

@foreach ($dataTable->scripts as $script)
    {!! $script !!}
@endforeach
@endscript
