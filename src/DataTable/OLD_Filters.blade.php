@aware(['__dataTable'])
{{--
@props([
'actionsColumn' => false,
'searchable' => false,
'filterable' => false,
])
--}}

@php
    /** @var ErickComp\LivewireDataTable\DataTable $__dataTable */

    $__dataTableFilters->fillComponentAttributeBags($attributes);
    $__dataTable->initFilters($__dataTableFilters);
    dd('hue-filterssssssssssss');


    //$__dataTable->addColumn($attributes, $actionsColumn, $searchable, $filterable);
@endphp
