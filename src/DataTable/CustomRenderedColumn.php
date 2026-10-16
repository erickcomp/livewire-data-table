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
        bool|string $exportable = true,
        ?string $exportField = null,
        ?string $editor = null,
        mixed $editorOptions = null,
        mixed $editorRules = null,
        ?string $editorField = null,
    ) {
        parent::__construct($title, $dataField, /*$name,*/ $searchable, $sortable, $exportable, $exportField, $editor, $editorOptions, $editorRules, $editorField);

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
