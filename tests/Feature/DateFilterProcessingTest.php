<?php

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Filters;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use ErickComp\LivewireDataTable\Livewire\Preset;
use Illuminate\View\ComponentAttributeBag;

/**
 * Regression: processFilters() parses date filter values with the Date facade, which was used without
 * being imported — every date/date-picker filter failed with "Class ...\Livewire\Date not found".
 */
it('parses the from/to values of a date range filter', function () {
    $dataTable = new DataTable(dataSrc: []);
    $dataTable->filters = new Filters(new ComponentAttributeBag([]), Preset::loadFromName('empty'));
    $dataTable->filters->filtersItems[] = new Filter(new ComponentAttributeBag([
        'data-field' => 'created_at',
        'input-type' => Filter::TYPE_DATE_PICKER,
        'label' => 'Created at',
    ]));

    $component = new LwDataTable();
    $component->filters = ['created_at' => ['created_at' => ['from' => '2024-01-01', 'to' => '2024-01-31']]];

    $processed = (function () use ($dataTable) {
        $this->dataTable = $dataTable;
        $this->processFilters();

        return $this->processedFilters;
    })->call($component);

    expect($processed)->toHaveCount(1)
        ->and($processed[0]['mode'])->toBe(Filter::MODE_RANGE)
        ->and($processed[0]['value']['from'])->toBeInstanceOf(\Carbon\CarbonInterface::class)
        ->and($processed[0]['value']['from']->toDateString())->toBe('2024-01-01')
        ->and($processed[0]['value']['to']->toDateString())->toBe('2024-01-31');
});
