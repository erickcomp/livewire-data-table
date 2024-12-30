<?php

namespace ErickComp\LivewireDataTable\Builders\Action;

use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\DataTable\Column;

class LinkAction extends BaseAction
{
    public ?string $route = null;
    public ?string $url = null;

    public function __construct(
        //Col $__dataTableColumn,
        ComponentAttributeBag $attributes,
        string $title,
        ?string $confirmMessage = null,
        ?string $confirmCustom = null,
        ?string $route = null,
        ?string $url = null,
    ) {
        parent::__construct(
            //$__dataTableColumn,
            $attributes,
            $title,
            $confirmMessage,
            $confirmCustom,
        );

        if (empty($url) && empty($route)) {
            throw new \LogicException('You must provide one of the following: [route], [url]');
        }

        if (!empty($url) && !empty($route)) {
            throw new \LogicException('You must provide only one of the following: [route], [url]');
        }

        $this->route = $route;
        $this->url = $url;
    }
}
