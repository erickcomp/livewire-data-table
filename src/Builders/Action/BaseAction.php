<?php

namespace ErickComp\LivewireDataTable\Builders\Action;

use ErickComp\LivewireDataTable\DataTable\Col;
use Illuminate\View\ComponentAttributeBag;

class BaseAction
{
    public function __construct(
        //public Col $__dataTableColumn,
        public ComponentAttributeBag $attributes,
        public string $title,
        ?string $confirmMessage = null,
        ?string $confirmCustom = null,
    ) {
        if (!empty($confirmMessage) && !empty($confirmCustom)) {
            throw new \LogicException('You can only use one of the following at the same time: [confirm-message], [confirm-custom]');
        }

        $this->confirmMessage = $confirmMessage;
        $this->confirmCustom = $confirmCustom;
    }
}
