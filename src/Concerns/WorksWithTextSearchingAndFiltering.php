<?php
namespace ErickComp\LivewireDataTable\Concerns;

trait WorksWithTextSearchingAndFiltering
{
    public const TEXT_MODE_EXACT = 'exact';
    public const TEXT_MODE_CONTAINS = 'contains';
    public const TEXT_MODE_STARTS_WITH = 'starts_with';
    public const TEXT_MODE_ENDS_WITH = 'ends_with';
    public const TEXT_MODE_FULLTEXT = 'fulltext';
}
