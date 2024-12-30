@aware(['__dataTable'])

{{--
@props([
'actions' => false,
'custom' => false,
'class' => false,
])
--}}

@php /** @var ErickComp\LivewireDataTable\DataTable $__dataTable */ @endphp

@php
    $__dataTable->addColumn($__dataTableColumn);
@endphp
