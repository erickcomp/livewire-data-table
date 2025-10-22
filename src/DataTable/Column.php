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

    public const SEARCH_STRATEGY_CONTAINS = 'contains';
    public const SEARCH_STRATEGY_STARTS_WITH = 'starts-with';
    public const SEARCH_STRATEGY_ENDS_WITH = 'ends-with';
    public const SEARCH_STRATEGY_FULLTEXT = 'fulltext';
    public const SEARCH_STRATEGIES = [
        self::SEARCH_STRATEGY_CONTAINS,
        self::SEARCH_STRATEGY_STARTS_WITH,
        self::SEARCH_STRATEGY_ENDS_WITH,
        self::SEARCH_STRATEGY_FULLTEXT,
    ];

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
    public $searchableStrategy = 'contains';
    public bool $sortable = false;

    public function __construct(
        string $title,
        ?string $dataField = null,
        bool|string $searchable = false,
        bool $sortable = false,
    ) {
        if (empty(\trim($dataField ?? '')) && ($searchable || $sortable)) {
            throw new \BadMethodCallException('The data-field attribute is required for searchable or sortable columns.');
        }

        $this->title = $title;
        $this->dataField = $dataField;
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
        return $this->searchable !== false;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function buildThAttributes($presetClass, int $rowsCount): ComponentAttributeBag
    {
        $thAttributes = $this->attributes->except(['class', 'style'])->merge($this->thAttributes->except(['class', 'style'])->all())
            ->class($this->thAttributes['class'] ?? [])
            ->class($this->attributes['class'] ?? [])
            ->class($presetClass)
            ->style($this->thAttributes['style'] ?? [])
            ->style($this->attributes['style'] ?? []);

        if ($thAttributes['style'] === ';') {
            unset($thAttributes['style']);
        }

        if ($this->isSortable() && $rowsCount > 1) {
            $thAttributes['wire:click'] = "setSortBy('{$this->dataField}')";
            $thAttributes['role'] = 'button';
        }

        return $thAttributes;
    }

    public function buildTdAttributes($presetClass): ComponentAttributeBag
    {
        $tdAttributes = $this->attributes->except(['class', 'style'])->merge($this->tdAttributes->except(['class', 'style'])->all())
            ->class($this->tdAttributes['class'] ?? [])
            ->class($this->attributes['class'] ?? [])
            ->class($presetClass)
            ->style($this->tdAttributes['style'] ?? [])
            ->style($this->attributes['style'] ?? []);

        if ($tdAttributes['style'] === ';') {
            unset($tdAttributes['style']);
        }

        return $tdAttributes;
    }

    protected function setupSearchable(bool|string $attributeValue)
    {
        if (\is_string($attributeValue)) {
            $filtered = \filter_var($attributeValue, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if (\is_bool($filtered)) {
                $searchable = $filtered;
            }
        }

        if (\is_bool($searchable)) {
            $this->searchable = $searchable;

            return;
        }

        if (!\in_array(\strtolower($searchable), static::SEARCH_STRATEGIES)) {
            $this->searchable = false;

            return;
        }

        $this->searchable = true;
        $this->searchableStrategy = \strtolower($searchable);
    }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'attributes', //default
            'th-search-input-' => 'thSearchInputAttributes',
            'th-' => 'thAttributes',
            'td-' => 'tdAttributes',
        ];
    }
}
