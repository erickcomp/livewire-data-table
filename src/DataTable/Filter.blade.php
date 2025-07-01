@aware(['__dataTable'])

@php
    /** @var ErickComp\LivewireDataTable\DataTable $__dataTable */

    //dd($__dataTableFilters, 'hue-filter', $__env);
    $__dataTable->addFilter($__dataTableFilter);
    //dd($__env);

    //$__dataTable->addColumn($attributes, $actionsColumn, $searchable, $filterable);
@endphp
