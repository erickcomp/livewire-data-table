<?php

namespace ErickComp\LivewireDataTable\Livewire;

class LwDataRetrievalParams
{
    /**
     * @param string[] $searchDataFields
     */
    public function __construct(
        public ?int $page,
        public ?string $perPage,
        public string $pageName,
        public ?string $search,
        public array|true $searchDataFields,
        public ?array $columnsSearch,
        public ?array $filters,
        public ?string $sortBy,
        public ?string $sortDir,
    ) {}
}
