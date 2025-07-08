<?php

namespace ErickComp\LivewireDataTable\DataTable;

class DataColumn extends Column
{
    public function __construct(
        string $title,
        string $dataField,
        //?string $name = null,
        bool $searchable = false,
        bool $sortable = false,
    ) {
        parent::__construct($title, $dataField, /*$name,*/ $searchable, $sortable);
    }
}
