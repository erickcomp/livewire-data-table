<?php

namespace ErickComp\LivewireDataTable;

use Livewire\Attributes\Url;
use Livewire\Component as LivewireComponent;

class LwDataTable extends LivewireComponent
{
    #[Url]
    public string $search;

    #[Url]
    public string $filters;

    #[Url]
    public string $columnsSearch;

    #[Url]
    public string $sort;

    public DataTable $dataTable;

    public function render()
    {
        $this->processSearch();
        $this->processColumnsSearch();
        $this->processFilters();
        $this->processSorting();

        $this->dataTable->loadData();

        return view()->file(__DIR__ . '/lw-data-table.blade.php');
    }

    public function runAction(string $action, ...$params)
    {
        $this->dataTable->runAction($action, ...$params);
    }

    protected function processSearch()
    {
        // process query string $data and set it on the DataTable object
    }

    protected function processColumnsSearch()
    {
        // process query string $data and set it on the DataTable object
    }

    protected function processFilters()
    {
        // process query string $data and set it on the DataTable object
    }

    protected function processSorting()
    {
        // process query string $data and set it on the DataTable object
    }

    protected function loadDataTable()
    {
        // process query string $data and set it on the DataTable object
    }

    protected function queryString()
    {
        return [
            'search' => [
                'as' => config('erickcomp-livewire-data-table.query-string-search', 'search'),
            ],
            'filters' => [
                'as' => config('erickcomp-livewire-data-table.query-string-label-filters', 'filters'),
            ],
            'columnsSearch' => [
                'as' => config('erickcomp-livewire-data-table.query-string-param-cols-search-', 'cols-search'),
            ],
        ];
    }
}
