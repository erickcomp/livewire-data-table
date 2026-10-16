{{--
    The "Actions" dropdown. Picking an action runs it on the selected rows (see runBulkAction() in the table's x-data),
    after the confirmation it may ask for; the dropdown then goes back to its placeholder. An action with options is
    an <optgroup>, with an entry per option.

    Attribute bags print their values as they are, so the values are escaped here. Bound attributes (":label") come
    already escaped by Blade, hence no double encoding.

    @var \ErickComp\LivewireDataTable\DataTable\BulkActions $bulkActions
    @var \ErickComp\LivewireDataTable\DataTable\BulkAction[] $actions The ones the user may run
    @var \ErickComp\LivewireDataTable\Livewire\Preset $preset
--}}
@php
    $escape = fn(string $value): string => e($value, false);
    $confirmAttributes = fn(\ErickComp\LivewireDataTable\DataTable\BulkAction $action) => $action->requiresConfirmation
        ? ['data-lw-dt-confirm' => $escape($action->confirmMessage()), 'data-lw-dt-confirm-title' => $escape($action->confirmTitle())]
        : [];
@endphp
<select {{ $bulkActions->selectAttributes($preset) }}
    aria-label="{!! $escape($bulkActions->placeholder()) !!}"
    x-on:change="runBulkAction($event.target)">
    <option value="" selected>{!! $escape($bulkActions->placeholder()) !!}</option>

    @foreach ($actions as $action)
        @if ($action->hasOptions())
            <optgroup {{ $action->attributes->merge(['label' => $escape($action->label)], escape: false) }}>
                @foreach ($action->options as $optionValue => $optionLabel)
                    <option {{ new \Illuminate\View\ComponentAttributeBag([
                        'value' => $escape($action->name . ':' . $optionValue),
                        'data-lw-dt-action' => $escape($action->name),
                        'data-lw-dt-option' => $escape((string) $optionValue),
                        ...$confirmAttributes($action),
                    ]) }}>{!! $escape($optionLabel) !!}</option>
                @endforeach
            </optgroup>
        @else
            <option {{ $action->attributes->merge([
                'value' => $escape($action->name),
                'data-lw-dt-action' => $escape($action->name),
                ...$confirmAttributes($action),
            ], escape: false) }}>{!! $escape($action->label) !!}</option>
        @endif
    @endforeach
</select>
