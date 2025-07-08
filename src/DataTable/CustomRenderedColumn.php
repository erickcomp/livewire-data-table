<?php

namespace ErickComp\LivewireDataTable\DataTable;

use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;

class CustomRenderedColumn extends Column
{
    public string $customRendererCode;

    public function __construct(
        string $title,
        string $customRendererCode,
        ?string $dataField = null,
        //?string $name = null,
        bool $searchable = false,
        bool $sortable = false,
    ) {
        parent::__construct($title, $dataField, /*$name,*/ $searchable, $sortable);

        $this->customRendererCode = $customRendererCode;

    }

    public static function fromComponentAttributeBag(ComponentAttributeBag $attributes, ...$extraArgs): static
    {
        if (!\array_key_exists('customRendererCode', $extraArgs)) {
            $extraArgs['customRendererCode'] = '';
        }

        return parent::fromComponentAttributeBag($attributes, ...$extraArgs);
    }
}
