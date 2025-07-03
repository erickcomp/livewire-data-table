<?php

namespace ErickComp\LivewireDataTable\Livewire;

use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Data\BuildsDataTableQuery;
use ErickComp\LivewireDataTable\DataTable\Data\ProvidesDataTableData;
use ErickComp\LivewireDataTable\DataTable\Data\SearchesDataTable;
use ErickComp\LivewireDataTable\DataTable\Data\SearchesDataTableColumns;
use ErickComp\LivewireDataTable\DataTable\Data\SortsDataTable;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\ServerExecutor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Support\Uri;
use Livewire\Attributes\Url;
use Livewire\Component as LivewireComponent;
use Livewire\WithPagination;

class LwDataTable extends LivewireComponent
{
    use WithPagination;

    protected const SORT_BY_NONE = '';
    protected const SORT_DIR_NONE = '';
    protected const SORT_DIR_ASC = 'ASC';
    protected const SORT_DIR_DESC = 'DESC';
    protected const SORT_DIR_TOGGLE = [
        self::SORT_DIR_NONE => self::SORT_DIR_ASC,
        self::SORT_DIR_ASC => self::SORT_DIR_DESC,
        self::SORT_DIR_DESC => self::SORT_DIR_NONE,
    ];

    #[Url]
    public string $search = '';

    #[Url]
    public array $filters = [];

    #[Url]
    public array $columnsSearch = [];

    #[Url]
    public string $sortBy = '';

    #[Url]
    public string $sortDir = '';

    public ?int $perPage = 5;
    public DataTable $dataTable;

    protected string $filterUrlParam {
        get => \config('erickcomp-livewire-data-table.query-string-filters', 'filters');
    }

    protected string $searchUrlParam {
        get => \config('erickcomp-livewire-data-table.query-string-search', 'search');
    }

    protected string $columnsSearchUrlParam {
        get => \config('erickcomp-livewire-data-table.query-string-param-cols-search', 'cols-search');
    }

    protected array $processedFilters = [];
    protected array $appliedFilters = [];

    public function mount()
    {
        //$this->updateFilters();
        //$this->updateSearch();
        //$this->processFilters();
    }

    public function render()
    {
        $this->processFilters();

        $rows = $this->getTableData();

        if ($rows instanceof LengthAwarePaginator && $this->paginators['page'] > $rows->lastPage()) {

            // Forces page reset on URI level
            $newUri = Uri::of(url()->full())
                ->withQuery(['page' => $rows->lastPage()])
                ->__tostring();

            $this->redirect($newUri, true);

        }

        $columnsSearchDebounceMs = config('erickcomp-livewire-data-table.columns-search-debounce-ms', 200);

        $shouldStylePagination = match ($this->dataTable::class::$useDefaultPaginationStylingForDefaultPaginationViews) {
            true => $this->dataTable->isUsingDefaultPaginationViews(),
            false => false,
            null => \get_class($this->dataTable) === DataTable::class && $this->dataTable->isUsingDefaultPaginationViews()
        };

        $this->setupSearch();

        //$inputSearchIdentifier = ($this->dataTable->name ?? $this->dataTable->id ?? $this->getId()) . '-search';
        //$buttonApplySearchIdentifier = "$inputSearchIdentifier-apply";

        $viewData = [
            'rows' => $rows,
            //'inputSearchIdentifier' => $inputSearchIdentifier,
            //'buttonApplySearchIdentifier' => $buttonApplySearchIdentifier,
            'columnsSearchDebounceMs' => $columnsSearchDebounceMs,
            'shouldStylePagination' => $shouldStylePagination,
            'filterUrlParam' => $this->filterUrlParam,
            'initialFilters' => $this->computeInitialFilters(),
            '___lwDataTable' => $this,
        ];

        return view()
            ->file(\substr(__FILE__, 0, -3) . 'blade.php')
            ->with($viewData);
    }

    public function paginationView(): string
    {
        return $this->dataTable->paginationView();
    }

    public function paginationSimpleView(): string
    {
        return $this->dataTable->paginationSimpleView();
    }

    public function updating(string $property, $value)
    {
        if (\in_array($property, ['search', 'filters']) || \str_starts_with($property, 'columnsSearch.')) {
            $this->resetPage();
        }
    }

    public function setSortBy(string $column, ?string $sortDir = null)
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $sortDir ?? self::SORT_DIR_TOGGLE[$this->sortDir] ?? self::SORT_DIR_NONE;

            if ($this->sortDir === self::SORT_DIR_NONE) {
                $this->sortBy = self::SORT_BY_NONE;
            }
        } else {
            $this->sortBy = $column;
            $this->sortDir = $sortDir ?? self::SORT_DIR_ASC;
        }
    }

    public function appliedFiltersData(): array
    {
        return $this->appliedFilters;
    }

    public function runAction(string $action, ...$params)
    {
        $this->dataTable->runAction($action, ...$params);
    }

    public function applyFilters(array $inputFilters)
    {
        $removeEmptyValues = function (array $data) use (&$removeEmptyValues) {
            $filtered = [];

            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $cleaned = $removeEmptyValues($value);

                    if (!empty($cleaned)) {
                        $filtered[$key] = $cleaned;
                    }

                } elseif ($value !== null && $value !== '' && $value !== []) {
                    $filtered[$key] = $value;
                }
            }

            return $filtered;
        };

        $filteredFilters = $removeEmptyValues($inputFilters);

        $this->filters = $filteredFilters;
    }

    protected function processColumnsSearch()
    {
        // process query string $data and set it on the DataTable object
    }

    protected function processFilters()
    {
        $this->processedFilters = [];
        $this->appliedFilters = [];

        $filtersItemsCollection = collect($this->dataTable->filters->filtersItems);

        foreach ($this->filters as $column => $filters) {
            foreach ($filters as $filterName => $filterVal) {

                /** @var Filter */
                $filterDefinition = $filtersItemsCollection->first(
                    fn(Filter $filterDefinition) => $filterDefinition->column === $column && $filterDefinition->name === $filterName
                );

                if ($filterDefinition) {
                    $this->processedFilters[] = [
                        'column' => $column,
                        'mode' => $filterDefinition->mode,
                        'value' => $filterVal,
                    ];

                    $isRangeMode = $filterDefinition->mode === Filter::MODE_RANGE;

                    $this->appliedFilters[] = [
                        'wire-name' => $filterDefinition->buildWireModelAttribute($this->filterUrlParam),
                        'name' => $filterDefinition->name,
                        'label' => str($filterDefinition->label . ': ')
                            ->when($isRangeMode, function (Stringable $string) use ($filterVal) {
                                return $string->append(($filterVal['from'] ?? '...') . ' - ' . ($filterVal['to'] ?? '...'));
                            })
                            ->unless($isRangeMode, function (Stringable $string) use ($filterVal) {
                                return $string->append((string) $filterVal);
                            })
                            ->toString(),
                    ];
                }
            }
        }
    }

    protected function computeInitialFilters()
    {
        $fullFilters = [];
        foreach ($this->dataTable->filters->filtersItems as $filterItem) {
            $filterValue = $this->filters[$filterItem->column][$filterItem->name]
                ?? ($filterItem->mode === Filter::MODE_RANGE ? ['from' => '', 'to' => ''] : '');

            $fullFilters[$filterItem->column][$filterItem->name] = $filterValue;
        }

        return $fullFilters;
    }

    protected function setupSearch()
    {
        $this->dataTable->search->inputAttributes->merge([
            'id' => ($this->dataTable->name ?? $this->dataTable->id ?? $this->getId()) . '-search',
            'name' => ($this->dataTable->name ?? $this->dataTable->id ?? $this->getId()) . '-search',
        ]);

        $this->dataTable->search->buttonAttributes->merge([
            'id' => ($this->dataTable->name ?? $this->dataTable->id ?? $this->getId()) . '-search-apply',
            'name' => ($this->dataTable->name ?? $this->dataTable->id ?? $this->getId()) . '-search-apply',
        ]);

        if (empty($this->dataTable->search->dataFields)) {
            $this->dataTable->search->setDataFieldsFromDataTable($this->dataTable);
        }
    }

    protected function processSorting()
    {
        // process query string $data and set it on the DataTable object
    }

    protected function getTableData()
    {
        if (!$this->dataTable->dataProvider) {
            return [];
        }

        $params = new LwDataRetrievalParams(
            page: Paginator::resolveCurrentPage($this->dataTable->pageName),
            perPage: $this->perPage,
            search: $this->search,
            columnsSearch: $this->columnsSearch,
            filters: $this->processedFilters,
            sortBy: $this->sortBy,
            sortDir: $this->sortDir,
        );

        return $this->getDataFromDataProvider($params);
    }

    protected function getDataFromDataProvider(LwDataRetrievalParams $params)
    {
        return match (true) {
            $this->dataProviderProvidesDataTableData() => $this->getDataUsingDataProviderObject($params),
            $this->dataProviderBuildsDataTableQuery() => $this->getDataUsingDataTableQuery($params),
            $this->dataProviderIsEloquentModel() => $this->getDataUsingEloquenModel($params),
            $this->dataProviderIsClass() => $this->getDataUsingClassObject($params),
            $this->dataProviderIsCallable() => $this->getDataUsingCallable($params),
            default => throw new \LogicException("Cannot get data from [{$this->dataTable->dataProvider}]")
        };

        // process query string $data and set it on the DataTable object
        //return $this->executeCallable($this->dataTable->dataProvider, ...$params);
    }

    protected function getDataUsingDataProviderObject(LwDataRetrievalParams $params): LengthAwarePaginator
    {
        /** @var ProvidesDataTableData */
        $dataProvider = App::make($this->dataTable->dataProvider);

        return $dataProvider->dataTableData($params);
    }

    protected function getDataUsingDataTableQuery(LwDataRetrievalParams $params): LengthAwarePaginator
    {
        /** @var BuildsDataTableQuery */
        $queryProvider = App::make($this->dataTable->dataProvider);

        return $queryProvider->buildLwDataTableQuery($params)->paginate();
    }

    protected function getDataUsingEloquenModel(LwDataRetrievalParams $params)
    {
        $model = $this->dataTable->dataProvider;

        /** @var EloquentBuilder $query */
        $query = (new $model)->query();

        $this->applyDataTableFiltersOnEloquentQuery($query, $params->filters);
        $this->applyDataTableColumnsSearchOnEloquentQuery($query, $params->columnsSearch);
        $this->applyDataTableSearchOnEloquentQuery($query, $params->search);
        $this->applyDataTableColumnsSortingOnEloquentQuery($query, $params->sortBy);
        $this->applyDataTableSortingDirectionOnEloquentQuery($query, $params->sortDir);

        return $query->paginate();
    }

    protected function getDataUsingClassObject(LwDataRetrievalParams $params)
    {
        //
        $dataProvideObject = App::make($this->dataTable->dataProvider);
        $dataProviderMethod = $this->dataTable->dataProviderGetDataMethod;

        return App::call([$dataProvideObject, $dataProviderMethod], ['params' => $params]);

    }

    protected function getDataUsingCallable(LwDataRetrievalParams $params)
    {
        //
    }

    protected function applyDataTableFiltersOnEloquentQuery(EloquentBuilder $query, ?array $filters)
    {
        //
        dd($filters);
    }

    protected function applyDataTableColumnsSearchOnEloquentQuery(EloquentBuilder $query, ?array $columnsSearch)
    {
        if (empty($columnsSearch)) {
            return;
        }

        if (\is_a($this->dataTable->dataProvider, SearchesDataTableColumns::class, true)) {
            $model = $this->dataTable->dataProvider;
            new $model()->applyLwDataTableColumnsSearch($query, $columnsSearch);
        } else {
            foreach ($columnsSearch as $col => $value) {
                $query->whereLike($col, "%$value%");
            }
        }
    }

    protected function applyDataTableSearchOnEloquentQuery(EloquentBuilder $query, ?string $search)
    {
        if (empty($search)) {
            return;
        }

        $modelClass = $this->dataTable->dataProvider;
        if (\is_a($this->dataTable->dataProvider, SearchesDataTable::class, true)) {
            new $modelClass()->applyLwDataTableColumnsSearch($query, $search);
        } else {
            $model = new $modelClass();
            $columnsToSearch = collect(Schema::getColumns($model->getTable()))
                ->pluck('name')
                ->diff($model->getHidden());

            foreach ($columnsToSearch as $col) {
                $query->whereLike($col, "%$search%");
            }
        }
    }

    protected function applyDataTableColumnsSortingOnEloquentQuery(EloquentBuilder $query, ?string $sortBy, string $sortDir = 'ASC')
    {
        if (empty(\trim($sortBy ?? ''))) {
            return;
        }

        $modelClass = $this->dataTable->dataProvider;
        if (\is_a($this->dataTable->dataProvider, SortsDataTable::class, true)) {
            new $modelClass()->applyLwDataTableSorting($query, $sortBy, $sortDir);
        } else {
            $sortDir = \in_array(\strtoupper($sortDir), ['ASC', 'DESC'])
                ? \strtoupper($sortDir)
                : 'ASC';

            $query->orderBy($sortBy, $sortDir);
        }
    }

    protected function applyDataTableSortingDirectionOnEloquentQuery(EloquentBuilder $query, ?string $sortDir)
    {
        if (empty($search)) {
            return;
        }

        $modelClass = $this->dataTable->dataProvider;
        if (\is_a($this->dataTable->dataProvider, SearchesDataTable::class, true)) {
            new $modelClass()->applyLwDataTableColumnsSearch($query, $search);
        } else {
            $model = new $modelClass();
            $columnsToSearch = collect(Schema::getColumns($model->getTable()))
                ->pluck('name')
                ->diff($model->getHidden());

            foreach ($columnsToSearch as $col) {
                $query->whereLike($col, "%$search%");
            }
        }
    }

    protected function dataProviderProvidesDataTableData(): bool
    {
        return \is_a($this->dataTable->dataProvider, ProvidesDataTableData::class, true);
    }

    protected function dataProviderBuildsDataTableQuery(): bool
    {
        return \is_a($this->dataTable->dataProvider, BuildsDataTableQuery::class, true);
    }

    protected function dataProviderIsEloquentModel(): bool
    {
        return \is_a($this->dataTable->dataProvider, EloquentModel::class, true);
    }

    protected function dataProviderIsClass(): bool
    {
        return \class_exists($this->dataTable->dataProvider);
    }

    protected function dataProviderIsCallable(): bool
    {
        if (\is_callable($this->dataTable->dataProvider)) {
            return true;
        }

        $callable = Str::parseCallback($this->dataTable->dataProvider);

        return \is_callable([$callable[0], $callable[1]])
            || \is_callable([App::make($callable[0]), $callable[1]]);
    }

    protected function executeCallable($callable, ...$params)
    {
        return ServerExecutor::call($callable, ...$params);
    }

    protected function queryString()
    {
        return [
            'search' => [
                'as' => $this->searchUrlParam,
            ],
            'filters' => [
                'as' => $this->filterUrlParam,
            ],
            'columnsSearch' => [
                'as' => $this->columnsSearchUrlParam,
            ],
        ];
    }
}
