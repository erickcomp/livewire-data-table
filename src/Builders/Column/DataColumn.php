<?php

namespace ErickComp\LivewireDataTable\Builders\Column;

use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\DataTable;

class DataColumn extends BaseColumn
{
    public null|string $dataField;
    //public array $tableSearchDataFields = [];
    //public array $columnSearchDataFields = [];
    //public string|false $sortable = false;
    //public string|false $sortableRaw = false;
    //public bool $isFilterable;

    public bool $searchable;
    public bool $filterable;
    public bool $sortable;

    public function __construct(
        //DataTable $__dataTable,
        string $name,
        string $title,
        ComponentAttributeBag $attributes,
        null|string $dataField = null,
        bool $searchable = false,
        bool $sortable = false,

    ) {
        parent::__construct(/*$__dataTable,*/ $name, $title, $attributes, $searchable, $sortable);

        $this->dataField = $dataField;

        // $this->setupTableSearchable($isTableSearchable);
        // $this->setupColumnSearchable($isColumnSearchable);
        // $this->setupSortable($isSortable, $sortableRaw);
    }

    public function searchableDataField(): string
    {
        return $this->searchable
            ? $this->dataField
            : null;
    }



    // public function usesRawSorting(): bool
    // {
    //     return \is_string($this->sortable) && !empty($this->sortable);
    // }

    // protected function setupTableSearchable(bool|string|array $isTableSearchable)
    // {
    //     if ($isTableSearchable === false) {
    //         $this->tableSearchDataFields = [];

    //         return;
    //     }

    //     if ($isTableSearchable === true) {
    //         if (!empty($this->dataField)) {
    //             $this->tableSearchDataFields = [$this->dataField];
    //         }

    //         return;
    //     }

    //     if (\is_string($isTableSearchable)) {
    //         // Double trim to remove all default trim chars plus comma
    //         $isTableSearchable = \trim(\trim($isTableSearchable, ','));
    //         $isTableSearchable = \array_map(fn($item) => trim($item), \explode(',', $isTableSearchable));
    //     }

    //     $this->tableSearchDataFields = $isTableSearchable;
    // }

    // protected function setupColumnSearchable(bool|string|array $isColumnSearchable)
    // {
    //     if ($isColumnSearchable === false) {
    //         $this->tableSearchDataFields = [];

    //         return;
    //     }

    //     if ($isColumnSearchable === true) {
    //         if (!empty($this->dataField)) {
    //             $this->tableSearchDataFields = [$this->dataField];
    //         }

    //         return;
    //     }

    //     if (\is_string($isColumnSearchable)) {
    //         // Double trim to remove all default trim chars plus comma
    //         $isColumnSearchable = \trim(\trim($isColumnSearchable, ','));
    //         $isColumnSearchable = \array_map(fn($item) => trim($item), \explode(',', $isTableSearchable));
    //     }

    //     $this->columnSearchDataFields = $isColumnSearchable;
    // }

    // protected function setupSortable(bool|string $isSortable, bool|string $sortableRaw)
    // {
    //     if ($isSortable === true) {
    //         if (!empty($sortableRaw)) {
    //             throw new \LogicException("You cannot use sortable and sortable-raw at the same time");
    //         }

    //         if (empty($this->dataField)) {
    //             throw new \LogicException("You cannot use sortable without using the attribute [data-field]. If you're using custom rendering, use sortable-raw instead");
    //         } else {
    //             $this->sortable = $this->dataField;
    //         }

    //         return;
    //     }

    //     if (!empty($sortableRaw)) {
    //         $this->sortableRaw = $sortableRaw;
    //     }
    // }
}
