<?php

namespace ErickComp\LivewireDataTable\DataTable;

use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use ErickComp\LivewireDataTable\Concerns\CreatesFromComponentAttributeBag;

class Column
{
    use CreatesFromComponentAttributeBag {
        fromComponentAttributeBag as traitFromComponentAttributeBag;
    }

    use FillsComponentAttributeBags;

    public const ATTRIBUTE_NAME = 'name';
    public const ATTRIBUTE_TITLE = 'title';
    public const ATTRIBUTE_DATA_FIELD = 'data-field';
    public const ATTRIBUTE_SEARCHABLE = 'searchable';
    public const ATTRIBUTE_SORTABLE = 'sortable';

    public ComponentAttributeBag $tdAttributes;
    public ComponentAttributeBag $thAttributes;
    public ComponentAttributeBag $thSearchInputAttributes;
    public ?string $dataField = null;
    public string $name;
    public string $title;
    public bool $searchable = false;
    public bool $sortable = false;

    public function __construct(
        string $title,
        ?string $dataField = null,
        //?string $name = null,
        bool $searchable = false,
        bool $sortable = false,
    ) {
        if (empty(\trim($dataField ?? '')) && ($searchable || $sortable)) {
            throw new \BadMethodCallException('The data-field attribute is required for searchable or sortable columns.');
        }

        // if (empty($name)) {
        //     $name = $dataField;
        // }

        // if (empty($name)) {
        //     throw new \BadMethodCallException('You must set at least one of the following attributes: "name" or "data-field".');
        // }

        $this->title = $title;
        $this->dataField = $dataField;
        //$this->name = $name;
        $this->searchable = $searchable;
        $this->sortable = $sortable;
    }

    public static function fromComponentAttributeBag(ComponentAttributeBag $attributes, ...$extraArgs): static
    {
        $instance = static::traitFromComponentAttributeBag($attributes, ...$extraArgs);

        $instance->fillComponentAttributeBags($instance->attributes);

        return $instance;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'tdAttributes', //default
            'th-search-input-' => 'thSearchInputAttributes',
            'th-' => 'thAttributes',
        ];
    }
}
