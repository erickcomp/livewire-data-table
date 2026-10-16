{{--
    One row action: a link (url or route), a form submitted with the CSRF token (url or route with another method than
    GET) or a button that runs the action on the server (handler or event). Each element carries the action's name,
    so a click on the row can click it (see onRowClick() in the table's x-data).

    Attribute bags print their values as they are, so the values are escaped here. Bound attributes (":label") come
    already escaped by Blade, hence no double encoding.

    @var \ErickComp\LivewireDataTable\DataTable\RowAction $action
    @var mixed $row
    @var string $rowId
    @var array $class The preset's classes for the element
    @var ?string $role "menuitem" in a dropdown
    @var \ErickComp\LivewireDataTable\Livewire\Preset $preset
--}}
@php
    $escape = fn(string $value): string => e($value, false);

    $confirmAttributes = $action->requiresConfirmation
        ? ['data-lw-dt-confirm' => $escape($action->confirmMessage($row)), 'data-lw-dt-confirm-title' => $escape($action->confirmTitle())]
        : [];

    $elementAttributes = $action->attributes->merge(['data-lw-dt-row-action' => $escape($action->name)], escape: false)
        ->class($class);

    if ($role !== null) {
        $elementAttributes = $elementAttributes->merge(['role' => $role], escape: false);
    }
@endphp
@if ($action->submitsForm())
    <form method="post"
        {{ new \Illuminate\View\ComponentAttributeBag([
            'action' => $escape($action->href($row)),
            'target' => $action->target === null ? null : $escape($action->target),
            ...$confirmAttributes,
        ]) }}
        @class($preset->get('table.row-actions.form.class', []))
        x-on:submit.prevent="submitRowActionForm($el)">
        @csrf
        @if ($action->method !== 'post')
            @method(strtoupper($action->method))
        @endif
        <button type="submit" {{ $elementAttributes }}>{!! $escape($action->label) !!}</button>
    </form>
@elseif ($action->isLink())
    <a {{ $elementAttributes->merge([
            'href' => $escape($action->href($row)),
            'target' => $action->target === null ? null : $escape($action->target),
            'rel' => $action->target === null || $action->target === '_self' ? null : 'noopener',
            ...$confirmAttributes,
        ], escape: false) }}
        @if ($action->requiresConfirmation) x-on:click.prevent="followRowLink($el)" @endif>{!! $escape($action->label) !!}</a>
@else
    <button type="button"
        {{ $elementAttributes->merge(['data-lw-dt-row-id' => $escape($rowId), ...$confirmAttributes], escape: false) }}
        x-on:click="runRowAction($el)">{!! $escape($action->label) !!}</button>
@endif
