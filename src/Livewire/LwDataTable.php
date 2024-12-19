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

    public function triggerUserAction(string $userAction, ...$params)
    {
        $this->dataTable->userAction($userAction, ...$params);
    }

    protected function processSearch()
    {
        // process query string $data and
    }

    protected function processColumnsSearch()
    {
        // process query string $data and
    }

    protected function processFilters()
    {
        // process query string $data and
    }

    protected function processSorting()
    {
        // process query string $data and
    }

    protected function loadDataTable()
    {
        // process query string $data and
    }
}
