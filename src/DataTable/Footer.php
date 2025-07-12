<?php

namespace ErickComp\LivewireDataTable\DataTable;

use Illuminate\View\ComponentAttributeBag;

class Footer
{
    public function __construct(
        public ComponentAttributeBag $attributes,
        public string $rendererCode,
    ) {}

}
