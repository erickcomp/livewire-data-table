@php
    /** @var \App\Views\BladeComponents\DataTable $dataTable */
@endphp

<table {{ $dataTable->tableAttributes }}>
    <thead {{ $dataTable->theadAttributes }}>
        {{--
        //@if (isset($dataTable->actions) && $dataTable->actions->position === TableActions::POSITION_START)
        //    <th class {{ $dataTable->actionColumnHeader->attributes }}>{{ $dataTable->actionColumnHeader->label }}
        //@endif
        //
        //foreach($dataTable->columnHeaders as $columnHeader )
        //    <th class {{ $dataTable->actionColumnHeader->attributes }}>{{ $dataTable->actionColumnHeader->label }}
        //@endforeach
        //
        //@if (isset($dataTable->actions) && $dataTable->actions->position === TableActions::POSITION_END)
        //    <th class {{ $dataTable->actionColumnHeader->attributes }}>{{ $dataTable->actionColumnHeader->label }}
        //@endif
        --}}

        @foreach ($dataTable->columns as $column)
            <th {{ $column->header->attributes }}>{{ $column->header->label }}</th>
        @endforeach
    </thead>
    <tbody {{ $dataTable->tbodyAttributes }}>
        {{ $slot }}

        @forelse($dataTable->rows as $row)
            @php $__dataTableRow = $row; @endphp
            @if ($dataTable->hasSeachableColumns())
                @foreach ($dataTable->columns as $column)
                    @if (!$column->isSearchable)
                        <td {{ $column->$tdAttributes }}> </td>
                        @continue
                    @endif

                    <td {{ $column->$tdAttributes }}>
                        <input type="text" wire:model.live="columnsSearch.{{ $column->dataField }}" />
                    </td>
                @endforeach
            @endif

            <tr {{ $dataTable->trAttributes }}>
                @foreach ($dataTable->columns as $column)
                    @if ($column->isActionColumn)
                        <td {{ $column->attributes->merge($column->actions->attributes) }}>

                            @if ($column->hasCustomRenderer())
                                {{-- eval ? Blade::render? --}}
                                @php
                                    \Illuminate\Support\Facades\Blade::render($column->customRenderer, ['__dataTableRow' => $row]);
                                @endphp
                            @elseif ($column->actions->type === Builders\Action::TYPE_BUTTON)
                                @foreach ($column->actions as $columnAction)
                                    @if ($columnAction->hasGate && $columnAction->can($row))
                                        <button {{ $columnAction->attributes }}
                                                wire:click="triggerUserAction($columnAction->action, $row->{$dataTable->dataSrcId})">
                                            {{-- @TODO: Verificar botão com ícone --}}
                                            {{ $columnAction->label }}
                                        </button>
                                    @endif
                                @endforeach
                            @elseif($column->actions->type === Builders\Action::TYPE_DROPDOWN)
                                {{-- <div class="erickcomp-livewire-data-table-actions-dropdown-container"> --}}
                                <div {{ $actions->dropdownContainerAttributes }}>
                                    {{--  <div class="erickcomp-livewire-data-table-actions-dropdown"> --}}
                                    <div {{ $actions->dropdownAttributes }}>
                                        <button
                                                {{ $actions->dropdownButtonAttributes }}>{{ $actions->dropdownLabel ?? __('Actions') }}</button>
                                        <div class="erickcomp-livewire-data-table-actions-dropdown-options">
                                            @foreach ($column->actions as $columnAction)
                                                @if ($columnAction->hasGate && $columnAction->can($row))
                                                    <a href="#"
                                                       wire:click="triggerUserAction($columnAction->action, $row->{$dataTable->dataSrcId})">
                                                        {{-- @TODO: Verificar dropdown option com ícone --}}
                                                        {{ $columnAction->label }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif


                        <td {{ $dataTable->actions->attributes }}>
                            {{-- Usar components aqui para poder implementar diferentes tipos de colunas de action? --}}
                            @if ($dataTable->actions->type === TableActions::TYPE_BUTTONS)
                                @foreach ($dataTable->actions as $tableAction)
                                    <button {{ $tableAction->attributes }}
                                            wire:click="triggerUserAction($tableAction->action, $row->id)">
                                @endforeach
                            @else
                                <div class="dropdown"> // WHAT???
                                    @foreach ($dataTable->actions as $tableAction)
                                        <button {{ $tableAction->attributes }}
                                                wire:click="actionButtonClick($tableAction->action, $row->id )">
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    @elseif($column->isEditable)
                        {{-- @TODO: Implementar editable cell --}}
                        <td {{ $column->$tdAttributes }}>{{ $row->{$column->dataField} }}</td>
                    @else
                        <td {{ $column->$tdAttributes }}>{{ $row->{$column->dataField} }}</td>
                    @endif
                @endforeach
            </tr>
        @else
            <tr>
                <td colspan="{{ count($dataTable->columns) }}"> {{ $dataTable->noDataFoundMessage ?? __('No data') }}
                </td>
            </tr>
        @endforelse
    </tbody>
    @if (!\empty($dataTable->tfooter))
        <tfoot {{ $dataTable->tfooter->attributes }}>
            {{ $dataTable->tfooter->slot }}
        </tfoot>
    @endif
</table>

<dialog x-init @dialog:open.window="$el.showModal()">
    <div id="lw-data-table-modal-content"></div>
    <form method="dialog" novalidate><button>{{ __('Close') }}</button></form>
</dialog>
