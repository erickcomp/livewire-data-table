<?php

namespace ErickComp\LivewireDataTable\Livewire;

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Search;

class LwDataRetrievalParams
{
    /**
     * @param string[] $searchDataFields
     */
    public function __construct(
        public ?int $page,
        public ?string $perPage,
        public string $pageName,
        public ?string $search,
        //public array|true $searchDataFields,
        public ?array $columnsSearch,
        public ?array $filters,
        public ?string $sortBy,
        public ?string $sortDir,
        protected DataTable $dataTable,
    ) {}

    public function columnSearchMode(string $column): string
    {
        return Column::SEARCH_MODE_DEFAULT;
    }

    public function dataTableSearchDataFields(): bool|array
    {
        return $this->dataTable->search->dataFields;
    }

    public function searchModeForDataField(string $dataField): string
    {
        return Search::SEARCH_MODE_DEFAULT;
    }

    public function filterMode(string $filter)
    {
        return Filter::MODE_CONTAINS;
    }
}
