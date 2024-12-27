<?php

namespace ErickComp\LivewireDataTable\Livewire;

use ErickComp\LivewireDataTable\ServerExecutor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component as LivewireComponent;
use ErickComp\LivewireDataTable\DataTable;
use Livewire\WithPagination;
use Illuminate\Support\Uri;

class LwDataTable extends LivewireComponent
{
    use WithPagination;

    protected const SORT_BY_NONE = '';
    protected const SORT_DIR_NONE = '';
    protected const SORT_DIR_ASC = 'ASC';
    protected const SORT_DIR_DESC = 'DESC';
    protected const SORT_DIR_TOGGLE = [
        self::SORT_DIR_NONE => self::SORT_DIR_ASC,
        self::SORT_DIR_ASC => self::SORT_DIR_DESC,
        self::SORT_DIR_DESC => self::SORT_DIR_NONE,
    ];

    #[Url]
    public string $search = '';

    #[Url]
    public string $filters = '';

    #[Url]
    public array $columnsSearch = [];

    #[Url]
    public string $sortBy = '';

    #[Url]
    public string $sortDir = '';

    public DataTable $dataTable;

    public function render()
    {
        $this->processSearch();
        $this->processColumnsSearch();
        $this->processFilters();
        $this->processSorting();

        $rows = $this->getTableData();

        if ($rows instanceof LengthAwarePaginator) {
            if ($this->paginators['page'] > $rows->lastPage()) {

                // Forces page reset on URI level
                $newUri = Uri::of(url()->full())
                    ->withQuery(['page' => $rows->lastPage()])
                    ->__tostring();

                $this->redirect($newUri, true);
            }
        }

        $searchDebounceMs = config('erickcomp-livewire-data-table.search-debounce-ms', 200);

        $viewData = [
            'rows' => $rows,
            'searchDebounceMs' => $searchDebounceMs,
        ];

        return view()
            ->file(\substr(__FILE__, 0, -3) . 'blade.php')
            ->with($viewData);
    }

    public function updating(string $property, $value)
    {
        if (\in_array($property, ['search', 'filters']) || \str_starts_with($property, 'columnsSearch.')) {
            $this->resetPage();
        }
    }

    public function setSortBy(string $column, ?string $sortDir = null)
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $sortDir ?? self::SORT_DIR_TOGGLE[$this->sortDir] ?? self::SORT_DIR_NONE;

            if ($this->sortDir === self::SORT_DIR_NONE) {
                $this->sortBy = self::SORT_BY_NONE;
            }
        } else {
            $this->sortBy = $column;
            $this->sortDir = $sortDir ?? self::SORT_DIR_ASC;
        }
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

    protected function getTableData()
    {
        if (!$this->dataTable->dataSrc) {
            return [];
        }

        $params = [
            'page' => null,
            'perPage' => null,
            'search' => null,
            'columnsSearch' => $this->columnsSearch,
            'filters' => null,
            'sortBy' => $this->sortBy,
            'sortDir' => $this->sortDir,
        ];

        // process query string $data and set it on the DataTable object
        return $this->executeCallable($this->dataTable->dataSrc, ...$params);

    }

    protected function executeCallable($callable, ...$params)
    {
        return ServerExecutor::call($callable, ...$params);
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
