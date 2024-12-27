<?php

namespace ErickComp\LivewireDataTable\Builders\Column;

use ErickComp\LivewireDataTable\DataTable;
use Illuminate\View\ComponentAttributeBag;

class ActionsColumn extends BaseColumn
{
    public array $actions;

    public function __construct(
        //DataTable $__dataTable,
        string $name,
        string $title,
        ComponentAttributeBag $attributes,
        array $actions,

    ) {
        parent::__construct(/*$__dataTable,*/ $name, $title, $attributes, false, false);

        $this->actions = $actions;
    }
}
