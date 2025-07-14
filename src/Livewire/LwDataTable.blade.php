<?php

use ErickComp\LivewireDataTable\DataTable\CustomRenderedColumn;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;


/** @var \ErickComp\LivewireDataTable\DataTable $this->dataTable */
/** @var \ErickComp\LivewireDataTable\Livewire\LwDataTable $this */
/** @var \ErickComp\LivewireDataTable\DataTable\Filter $filterItem */

$thAttributes = function ($columnThAttributes, $tableThAttributes): ComponentAttributeBag {
    return $columnThAttributes->merge($tableThAttributes->all());
};

//$onClickSortableColumn = function (BaseColumn $column): string {
//    return $column->isSortable() ? 'wire:click="setSortBy(\'' . $column->name . '\')"' : '';
//};
?>
<div {{ $this->dataTable->containerAttributes->class([...$this->preset()->get('main-container.class'), 'lw-dt']) }}
    x-data="{!! $this->xData() !!}">
    @if($this->dataTable->hasTableActions())
        <div @class($this->preset()->get('actions.container.class'))>
            <div @class($this->preset()->get('actions.row.class'))>
                @if($this->dataTable->isSearchable())
                    <div {{ $this->dataTable->search->componentAttributes->class($this->preset()->get('search.container.class')) }}>
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
                            <input {{ $this->dataTable->search->inputAttributes->class($this->preset()->get('search.input.class')) }} />

                            <button {{ $this->dataTable->search->buttonAttributes->class($this->preset()->get('search.button.class')) }}>
                                @if($this->dataTable->search->shouldShowIconOnApplyButton())
                                    {!! $this->preset()->get('search.button.icon') !!}
                                @endif
                                @lang('erickcomp_lw_data_table::messages.search_button_label')
                            </button>
                        @endif
                    </div>
                @endif

                @if($this->dataTable->isFilterable() && $this->dataTable->filters->isCollapsible())
                    <button {{ $this->dataTable->filters->buttonToggleAttributes->class([...$this->preset()->get('filters.toggle-button.class'), 'active' => $this->shouldShowFiltersContainer()]) }}
                        x-bind:class="{ 'active': filtersContainerIsOpen }">

                        @if($this->dataTable->filters->shouldShowIconOnToggleButton())
                            {!! $this->preset()->get('filters.toggle-button.icon') !!}
                        @endif
                        
                        @lang('erickcomp_lw_data_table::messages.toggle_filters_button_label')
                    </button>
                @endif
            </div> <!-- end: lw-dt-table-actions-row -->
            @if($this->dataTable->isFilterable())
                <div @class($this->preset()->get('actions.row.class'))>
                    <div {{ $this->dataTable->filters->containerAttributes()->class($this->preset()->get('filters.container.class')) }}>
                        @if(!$this->dataTable->filters->isCollapsible())
                            <span @class($this->preset()->get('filters.title.class'))>
                                {{ $this->dataTable->filters->title() }}
                            </span>
                        @endif
                        @php $renderedFilterItemsNames = []; @endphp
                        @foreach($this->dataTable->filters->filtersItems as $filterItem)
                            <div @class($this->preset()->get('filters.item.class'))>
                                <div @class([
                                    ...$this->preset()->get('filters.item.content.class') ,
                                    ...($filterItem->mode === Filter::MODE_RANGE ? $this->preset()->get('filters.item.content.range.class') : [])
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

                                        <legend @class($this->preset()->get('filters.item.content.legend.class'))>
                                            {{-- ilterItem-?label . ?({$filterItem-?inputType})? --}}
                                            <span @class($this->preset()->get('filters.item.content.legend.span.class'))>
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
                                                x-model="{{ $filterItem->buildXModelAttribute('inputFilters') }}"
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
                                                    @lang('erickcomp_lw_data_table::messages.range_filter_label_from'):
                                                </span>
                                                <input
                                                    type="{{ $filterItem->htmlInputType() }}"
                                                    {{ $filterItem->inputAttributes(except: 'name')->class($inputFromClasses['class']) }}
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
                                                    @lang('erickcomp_lw_data_table::messages.range_filter_label_to'):
                                                </span>
                                                <input
                                                    type="{{ $filterItem->htmlInputType() }}"
                                                    {{ $filterItem->inputAttributes(except: 'name')->class($inputToClasses['class']) }}
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
                            <button {{ $this->dataTable->filters->buttonApplyAttributes->class($this->preset()->get('filters.apply-button.class')) }}>
                                @lang('erickcomp_lw_data_table::messages.apply_filters_button_label')
                            </button>
                        </div>
                    </div> {{-- end: $this->dataTable->filters->containerAttributes --}}

                </div>
            @endif

            @if (!empty($search) || (!empty($this->dataTable->filters) && !empty($this->appliedFiltersData())))
                <div @class($this->preset()->get('actions.row.class'))>
                    <div @class($this->preset()->get('applied-filters.container.class')) >
                        @if(count($this->appliedFiltersData()) > 0)
                            <span @class($this->preset()->get('applied-filters.label.class'))>
                                @lang('erickcomp_lw_data_table::messages.active_filters_label'):
                            </span>
                        @endif
                        @if(!empty(\trim($search)))

                            <span @class($this->preset()->get('applied-filters.applied-filter-item.class'))>
                                {{-- <button wire:click="clearSearch()">x</button> --}}
                                <button
                                    x-on:click="clearSearch()"
                                    @class($this->preset()->get('applied-filters.button-remove-applied-filter-item.class'))
                                    >
                                    {!! $this->preset()->get('applied-filters.button-remove-applied-filter-item.content') !!}
                                </button>
                                @lang('erickcomp_lw_data_table::messages.applied_search_label'): "{{ $search }}"
                            </span>

                        @endif

                        @foreach ($this->appliedFiltersData() as $appliedFilterData)
                            <span @class($this->preset()->get('applied-filters.applied-filter-item.class'))>
                                {{-- <button wire:click="removeFilter('{{ $appliedFilterData['wire-name'] }}')">x</button></button>
                                --}}
                                <button
                                    x-on:click="removeFilter('{{ Str::chopStart($appliedFilterData['wire-name'], "{$this->filtersUrlParam()}.") }}')"
                                    @class($this->preset()->get('applied-filters.button-remove-applied-filter-item.class'))>
                                    {!! $this->preset()->get('applied-filters.button-remove-applied-filter-item.content') !!}
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

    <table {{ $this->dataTable->tableAttributes->class($this->preset()->get('table.class')) }}>
        <thead {{ $this->dataTable->theadAttributes->class($this->preset()->get('table.thead.class')) }}>
            <tr {{ $this->dataTable->theadTrAttributes->class($this->preset()->get('table.thead.tr.class')) }}>
                @foreach ($this->dataTable->columns as $column)
                    @php
                        $thAttributes = $column->thAttributes->merge($this->dataTable->thAttributes->all());
                        //$thAttributes = $this->dataTable->thAttributes->merge($column->thAttributes->all());

                        if ($column->isSortable() && count($rows) > 1) {
                            $thAttributes['wire:click'] = "setSortBy('{$column->dataField}')";
                        }
                    @endphp
                    <th {{ $thAttributes->class($this->preset()->get('table.thead.tr.th.class')) }}>
                        {{ $column->title }}
                        @if ($column->isSortable() && count($rows) > 1 && $this->preset()->get('table.thead.tr.th.sorting.show-indicators'))
                            @php
                                $lowercaseSortDir = $column->dataField === $sortBy
                                    ? \strtolower(empty($sortDir) ? 'none' : $sortDir)
                                    : 'none';

                                $sortingPresetKey = "table.thead.tr.th.sorting.indicator-$lowercaseSortDir-class";
                            @endphp
                            <span @class($this->preset()->get($sortingPresetKey))></span>
                        {{--
                            @php
                                $columnSortClass = $column->dataField === $sortBy
                                    ? Str::kebab("{$this->dataTable->sortingClassPrefix}-" . \strtolower(empty($sortDir) ? 'none' : $sortDir))
                                    : "{$this->dataTable->sortingClassPrefix}-none";
                            @endphp
                            <span class="{{$this->dataTable->sortingClassPrefix}} {{ $columnSortClass }}"></span>
                        --}}
                        @endif
                        
                    </th>
                @endforeach
            </tr>
            
            @if($this->dataTable->hasSearchableColumns() && (count($rows) > 0 || !empty(\array_filter($columnsSearch))))
                <tr {{ $this->dataTable->theadSearchTrAttributes->class($this->preset()->get('table.thead.tr.search.class')) }}>
                    @foreach ($this->dataTable->columns as $column)
                        <th {{ $this->dataTable->theadSearchThAttributes->class($this->preset()->get('table.thead.tr.search.th.class')) }}>
                            @if ($column->isSearchable())
                                <input
                                    type="text"
                                    wire:model.live.debounce.{{ $this->preset()->get('table.thead.tr.search.debounce-ms') }}ms="columnsSearch.{{ $column->dataField }}"
                                    {{ $column->thSearchInputAttributes->class($this->preset()->get('table.thead.tr.search.th.input.class')) }}
                                    />
                            @endif
                        </th>
                    @endforeach
                </tr>
            @endif
        </thead>
        <tbody {{$this->dataTable->tbodyAttributes->class($this->preset()->get('table.tbody.class')) }}>
            
            @forelse ($rows as $row)
                @php
                    $trAttributes = new ComponentAttributeBag();
                    $trAttributesModifierCode = $this->dataTable->getTrAttributesModifierCode();
                @endphp
                @if(!empty($trAttributesModifierCode))
                    @php
                        $trAttributes = clone $this->dataTable->tbodyTrAttributes;

                        $modifierViewData = [
                            '___lwDataTable' => $this,
                            //'__dataTable' => $this->dataTable,
                            '___row' => $row,
                            '___trAttributes' => $trAttributes,
                            'loop' => $loop,
                        ];
                    @endphp
                    {!! Blade::render($trAttributesModifierCode, $modifierViewData) !!}
                @else
                    @php
                        $trAttributes = $this->dataTable->tbodyTrAttributes->class($this->preset()->get('table.tbody.tr.class'));
                    @endphp
                @endif

                <tr {{ $trAttributes }} wire:key="{{ $row->{$this->dataTable->dataIdentityColumn} }}">
                    @foreach ($this->dataTable->columns as $column)
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
                                <td {{ $column->tdAttributes?->class($this->preset()->get('table.tbody.tr.td.class')) }}>
                                    {!! $customRenderedColumn !!}
                                </td>
                            @endif

                            @continue
                        @endif

                        <td {{ $column->tdAttributes?->class($this->preset()->get('table.tbody.tr.td.class')) }} >
                            @if (!empty($customRenderedColumn))
                                {!! $customRenderedColumn !!}
                            @elseif($column instanceof DataColumn)
                                {{ $row->{$column->dataField} }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr {{ $this->dataTable->tbodyTrAttributes->class($this->preset()->get('table.tbody.tr.nodatafound.class')) }}>
                    <td class="lw-dt-nodatafound-td" colspan="{{ max([count($this->dataTable->columns), 1]) }}">
                        @lang('erickcomp_lw_data_table::messages.no_data_found_table_td_text')
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

    @if (\is_object($rows) && \method_exists($rows, 'links'))
        {{-- @TODO: Create params to choose between pagination styles --}}
        @if($this->dataTable->paginationCode != null)
            $paginationVars = [
            '__dataTable' => $this->dataTable,
            '__rows' => $rows
            ];
            {!! Blade::render($this->dataTable->paginationCode, $paginationVars) !!}
        @else
            <div @class(['lw-dt-pagination-container'])>
                {{ $rows->links() }}
            </div>
        @endif
    @endif
</div>

@assets

@foreach ($this->preset()->get('assets') as $asset)
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
            inputSearch: {{ Illuminate\Support\Js::from($search) }},
            inputFilters: {{ Illuminate\Support\Js::from($this->computeInitialFilters()) }},

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

@foreach ($this->preset()->get('scripts') as $script)
    {!! $script !!}
@endforeach

@foreach ($this->dataTable->scripts as $script)
    {!! $script !!}
@endforeach
@endscript
