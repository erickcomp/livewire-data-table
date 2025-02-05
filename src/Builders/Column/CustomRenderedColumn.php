<?php

namespace ErickComp\LivewireDataTable\Builders\Column;

use Illuminate\View\ComponentAttributeBag;

class CustomRenderedColumn extends BaseColumn
{
    public string $customRendererCode;

    public function __construct(
        //DataTable $__dataTable,
        string $name,
        string $title,
        ComponentAttributeBag $attributes,
        string $customRendererCode,
        bool $searchable = false,
        bool $sortable = false,

    ) {
        parent::__construct(/*$__dataTable,*/ $name, $title, $attributes, $searchable, $sortable);
        $this->customRendererCode = $customRendererCode;
    }
}
