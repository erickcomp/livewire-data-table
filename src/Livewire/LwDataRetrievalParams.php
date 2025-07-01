<?php

namespace ErickComp\LivewireDataTable\Livewire;

class LwDataRetrievalParams
{
    public function __construct(
        public protected(set) ?int $page,
        public protected(set) ?int $perPage,
        public protected(set) ?string $search,
        public protected(set) ?array $columnsSearch,
        public protected(set) ?array $filters,
        public protected(set) ?string $sortBy,
        public protected(set) ?string $sortDir,
    ) {}
}
