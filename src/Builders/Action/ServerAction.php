<?php

namespace ErickComp\LivewireDataTable\Builders\Action;

use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\DataTable\Column;

class ServerAction extends BaseAction
{
    public string|array $action;
    public ?string $can;
    public string $return;

    public function __construct(
        //Col $__dataTableColumn,
        ComponentAttributeBag $attributes,
        string $title,
        string|array $action,
        ?string $confirmMessage = null,
        ?string $confirmCustom = null,
        ?string $can = null,
        string $return = "alert",
    ) {
        parent::__construct(
            //$__dataTableColumn,
            $attributes,
            $title,
            $confirmMessage,
            $confirmCustom,
        );

        $this->action = $action;
        $this->can = $can;
        $this->return = $return;
    }
}
