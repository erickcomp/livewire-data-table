{{--
    A cell of the row being edited inline: the column's input, bound to $editingValues (deferred: the values travel with
    the save), and the field's validation message. Enter saves and Escape cancels (see onInlineEditKeydown() in the
    table's x-data, on the row).

    Attribute bags print their values as they are, so the title is escaped here. Bound attributes come already escaped
    by Blade, hence no double encoding.

    @var \ErickComp\LivewireDataTable\DataTable\Column $column
    @var \Illuminate\View\ComponentAttributeBag $tdAttributes
    @var bool $autofocus Whether the input takes the focus (the first one of the row)
    @var \ErickComp\LivewireDataTable\Livewire\Preset $preset
--}}
@php
    $editor = $column->editor;
    $model = 'editingValues.' . $editor->field;
    $hasError = $errors->has($model);
    $isCheckbox = $editor->type === \ErickComp\LivewireDataTable\DataTable\ColumnEditor::CHECKBOX;

    $userAttributes = $column->editorInputAttributes ?? new \Illuminate\View\ComponentAttributeBag();

    $inputAttributes = $userAttributes
        ->merge(\array_filter([
            'wire:model' => $model,
            'aria-label' => e($column->title, false),
            'aria-invalid' => $hasError ? 'true' : null,
            'step' => $editor->type === \ErickComp\LivewireDataTable\DataTable\ColumnEditor::NUMBER && !$userAttributes->has('step') ? 'any' : null,
            'x-init' => $autofocus ? '$nextTick(() => $el.focus())' : null,
        ], fn(?string $value) => $value !== null), escape: false);

    $inputClasses = [
        ...$preset->get($isCheckbox ? 'table.inline-edit.checkbox.class' : 'table.inline-edit.input.class', []),
        ...($hasError ? $preset->get('table.inline-edit.invalid.class', []) : []),
    ];

    if (!empty($inputClasses)) {
        $inputAttributes = $inputAttributes->class($inputClasses);
    }
@endphp
<td {{ $tdAttributes }}>
    @if ($editor->type === \ErickComp\LivewireDataTable\DataTable\ColumnEditor::SELECT)
        <select {{ $inputAttributes }}>
            @foreach ($editor->options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($editor->type === \ErickComp\LivewireDataTable\DataTable\ColumnEditor::TEXTAREA)
        <textarea {{ $inputAttributes }}></textarea>
    @else
        <input type="{{ $editor->htmlInputType() }}" {{ $inputAttributes }} />
    @endif

    @error($model)
        <div @class($preset->get('table.inline-edit.error.class', []))>{{ $message }}</div>
    @enderror
</td>
