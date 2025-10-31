<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Concerns\CreatesFromComponentAttributeBag;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use ErickComp\LivewireDataTable\Concerns\WorksWithTextSearchingAndFiltering;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\Support\Arr;

class Column
{
    use CreatesFromComponentAttributeBag {
        fromComponentAttributeBag as traitFromComponentAttributeBag;
    }

    use FillsComponentAttributeBags;
    use WorksWithTextSearchingAndFiltering;

    public const SEARCH_MODE_CONTAINS = self::TEXT_MODE_CONTAINS;
    public const SEARCH_MODE_STARTS_WITH = self::TEXT_MODE_STARTS_WITH;
    public const SEARCH_MODE_ENDS_WITH = self::TEXT_MODE_ENDS_WITH;
    public const SEARCH_MODE_EXACT = self::TEXT_MODE_EXACT;
    public const SEARCH_MODE_FULLTEXT = self::TEXT_MODE_FULLTEXT;
    public const SEARCH_MODE_DEFAULT = self::SEARCH_MODE_CONTAINS;

    public const SEARCH_MODES = [
        self::SEARCH_MODE_EXACT,
        self::SEARCH_MODE_CONTAINS,
        self::SEARCH_MODE_STARTS_WITH,
        self::SEARCH_MODE_ENDS_WITH,
        self::SEARCH_MODE_FULLTEXT,
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
    public $searchMode = 'contains';
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
        $this->dataField = $dataField;
        $this->title = $title;
        $this->sortable = $sortable;
        $this->setupSearchable($searchable);
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

    protected function setupSearchable(bool|string $searchable)
    {
        if (\is_string($searchable)) {
            $filtered = \filter_var($searchable, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if (\is_bool($filtered)) {
                $searchable = $filtered;
            }
        }

        if (\is_bool($searchable)) {
            $this->searchable = $searchable;
            $this->searchMode = static::SEARCH_MODE_DEFAULT;

            return;
        }

        if (!\in_array(\strtolower($searchable), static::SEARCH_MODES)) {
            throw new \ValueError('Invalid search mode for column [' . $this->dataField . ']: "' . $searchable . '". Valid modes are: ' . Arr::join(static::SEARCH_MODES, ', ', ' and '));
        }

        $this->searchable = true;
        $this->searchMode = \strtolower($searchable);
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
