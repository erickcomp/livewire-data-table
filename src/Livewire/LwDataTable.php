<?php

namespace ErickComp\LivewireDataTable\Livewire;

use ErickComp\LivewireDataTable\Data\EloquentDataSource;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\Data\BuildsDataTableQuery;
use ErickComp\LivewireDataTable\DataTable\Data\ProvidesDataTableData;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\ServerExecutor;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Livewire\Attributes\Locked;
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

    #[Locked]
    public array $rawFilters = [];

    #[Url]
    public array $columnsSearch = [];

    #[Url]
    public string $sortBy = '';

    #[Url]
    public string $sortDir = '';
    public ?bool $filtersContainerIsOpen = null;
    public ?string $perPage = '15';

    #[Locked]
    public string $dt;

    protected DataTable $dataTable;

    //protected string $preset = 'vanilla';
    protected array $processedFilters = [];
    protected array $appliedFilters = [];
    protected Preset $loadedPreset;

    public function render()
    {
        $this->setupMaxMemory();
        $this->processFilters();
        //$this->setupSearchAttributes();
        //$this->setupFiltersAttributes();

        $rows = $this->getTableData();

        if ($rows instanceof LengthAwarePaginator && ($this->paginators['page'] ?? 1) > $rows->lastPage()) {

            // Forces page reset to last page and re-evaluate the data
            $this->paginators['page'] = $rows->lastPage();

            $rows = $this->getTableData();

            // // Forces page reset on URI level
            // //$newUri = Uri::of(url()->full())
            // $fullUrl = $this->currentUrl;
            // $newUri = Uri::of($fullUrl)
            //     ->withQuery(['page' => $rows->lastPage(), ])
            //     ->__tostring();
            // 
            // $this->redirect($newUri, true);
        }

        //$inputSearchIdentifier = ($this->dataTable->name ?? $this->dataTable->id ?? $this->getId()) . '-search';
        //$buttonApplySearchIdentifier = "$inputSearchIdentifier-apply";

        $viewData = [
            'rows' => $rows,
            //'inputSearchIdentifier' => $inputSearchIdentifier,
            //'buttonApplySearchIdentifier' => $buttonApplySearchIdentifier,
            //'columnsSearchDebounceMs' => $columnsSearchDebounceMs,
            //'shouldStylePagination' => $shouldStylePagination,
            //'filterUrlParam' => $this->filterUrlParam,
            'initialFilters' => $this->computeInitialFilters(),
            //'___lwDataTable' => $this,
        ];

        return view()
            ->file(\substr(__FILE__, 0, -3) . 'blade.php')
            ->with($viewData);
    }

    public function preset(): Preset
    {
        if (!isset($this->loadedPreset)) {
            $this->loadedPreset = $this->dataTable->preset();
        }

        return $this->loadedPreset;
    }

    public function paginationView(): string
    {
        return isset($this->dataTable)
            ? $this->dataTable->paginationView()
            : '';
    }

    public function paginationSimpleView(): string
    {
        return isset($this->dataTable)
            ? $this->dataTable->paginationSimpleView()
            : '';
    }

    public function renderCustomPagination($rows)
    {
        $paginationVars = [
            '__dataTable' => $this->dataTable,
            '__rows' => $rows,
        ];

        return Blade::render($this->dataTable->paginationCode, $paginationVars);
    }

    public function renderPagination($rows)
    {
        return match (true) {
            \is_array($rows) || $rows instanceof Collection => '',
            $rows instanceof LengthAwarePaginatorContract => $rows->render($this->paginationView()),
            $rows instanceof PaginatorContract || $rows instanceof CursorPaginatorContract => $rows->render($this->paginationSimpleView()),
            \method_exists($rows, 'links') => $rows->links($this->dataTable->paginationView)
        };
    }

    public function xData(): string
    {
        $xData = "{
            storeId: '{$this->getId()}',

            dtData() {
                return Alpine.store(this.storeId);
            },

            get filtersContainerIsOpen() {
                return this.dtData().filtersContainerIsOpen;
            },

            get changedSearchTerms() {
                return this.dtData().changedSearchTerms(\$wire);
            },

            get changedFilters() {
                return this.dtData().changedFilters(\$wire);
            },

            applySearch() {
                this.dtData().applySearch(\$wire);
            },

            applyFilters() {
                this.dtData().applyFilters(\$wire);
            },

            clearSearch() {
                this.dtData().clearSearch(\$wire);
            },

            removeFilter(filter) {
                this.dtData().removeFilter(\$wire, filter);
            },

            toggleFiltersContainer(event) {
                this.dtData().toggleFiltersContainer(\$wire);
            }
        };";

        $xData = \preg_replace('/\s+/', ' ', $xData);
        $xData = \str_replace([' :', ': '], ':', $xData);
        $xData = \str_replace([' ,', ', '], ',', $xData);

        return \trim($xData);
    }

    public function updating(string $property, $value)
    {
        if (\in_array($property, ['search', 'filters', 'perPage']) || \str_starts_with($property, 'columnsSearch.')) {
            $this->resetPage();
        }
    }

    public function setSortBy(string $dataField, ?string $sortDir = null)
    {
        if ($this->sortBy === $dataField) {
            $this->sortDir = $sortDir ?? self::SORT_DIR_TOGGLE[$this->sortDir] ?? self::SORT_DIR_NONE;

            if ($this->sortDir === self::SORT_DIR_NONE) {
                $this->sortBy = self::SORT_BY_NONE;
            }
        } else {
            $this->sortBy = $dataField;
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

        $this->rawFilters = $inputFilters;
        $this->filters = $filteredFilters;
    }

    public function computeInitialFilters()
    {
        $fullFilters = [];
        foreach ($this->dataTable->filters?->filtersItems ?? [] as $filterItem) {
            $filterValue = $this->filters[$filterItem->dataField][$filterItem->name]
                ?? ($filterItem->mode === Filter::MODE_RANGE ? ['from' => '', 'to' => ''] : '');

            $fullFilters[$filterItem->dataField][$filterItem->name] = $filterValue;
        }

        return $fullFilters;
    }

    public function shouldShowFiltersContainer(): bool
    {
        if (!$this->dataTable->filters?->isCollapsible() ?? false) {
            return true;
        }

        if (\is_bool($this->filtersContainerIsOpen)) {
            return $this->filtersContainerIsOpen;
        }

        return !empty($this->filters);
    }

    public function isDataPaginated($rows): bool
    {
        return $rows instanceof \Illuminate\Contracts\Pagination\Paginator || $rows instanceof \Illuminate\Contracts\Pagination\CursorPaginator;
    }

    protected function mountDataTable(DataTable $dataTable)
    {
        $this->dataTable = $dataTable;
        $this->dt = DataTable::toCache($dataTable);
    }

    protected function hydrateDataTable()
    {
        $dataTable = DataTable::fromCache($this->dt);

        // Cache might have been busted for some reason (like a deployment or manually clearing the view cache)
        if (!$dataTable instanceof DataTable) {
            $this->skipHydrate();
            $this->skipRender();

            $reloadAlertConfig = $this->preset()->get('reload-alert', null);

            $reloadAlertConfig['alert-before-reload'] ??= true;
            $reloadAlertConfig['function-name'] ??= 'lwDataTableReloadAlert';

            if ($reloadAlertConfig['alert-before-reload'] === true) {
                $jsMessage = \Illuminate\Support\Js::from(__('erickcomp_lw_data_table::messages.reload_required'));
                $reloadJs = <<<JS
                        {$reloadAlertConfig['function-name']}({$jsMessage}, function () {window.location.reload();});
                    JS;
            } else {
                $reloadJs = 'window.location.reload();';
            }

            $this->js($reloadJs);

            return;
        }

        $this->dataTable = $dataTable;

        return null;
    }

    protected function filtersUrlParam(): string
    {
        return \config('erickcomp-livewire-data-table.query-string-filters', 'filters');
    }

    protected function searchUrlParam(): string
    {
        return \config('erickcomp-livewire-data-table.query-string-search', 'search');
    }

    protected function columnsSearchUrlParam(): string
    {
        return \config('erickcomp-livewire-data-table.query-string-param-cols-search', 'cols-search');
    }

    protected function processColumnsSearch()
    {
        // process query string $data and set it on the DataTable object
    }

    protected function setupMaxMemory()
    {
        if (\is_string($this->dataTable->phpMaxMemory)) {
            \ini_set('memory_limit', $this->dataTable->phpMaxMemory);
        }
    }

    protected function processFilters()
    {
        $datetimeTypes = [
            Filter::TYPE_DATE,
            Filter::TYPE_DATE_PICKER,
            Filter::TYPE_DATETIME,
            Filter::TYPE_DATETIME_PICKER,
        ];

        $this->processedFilters = [];
        $this->appliedFilters = [];

        $filtersItemsCollection = collect($this->dataTable->filters?->filtersItems ?? []);

        foreach ($this->filters as $dataField => $filters) {
            foreach ($filters as $filterName => $filterVal) {
                /** @var Filter */
                $filterDefinition = $filtersItemsCollection->first(
                    fn(Filter $filterDefinition) => $filterDefinition->dataField === $dataField && $filterDefinition->name === $filterName
                );

                $isRangeMode = $filterDefinition->mode === Filter::MODE_RANGE;

                // Custom formatting/parsing for date/time filter
                if (\in_array($filterDefinition->inputType, $datetimeTypes)) {
                    if ($isRangeMode) {
                        foreach (['from', 'to'] as $key) {
                            if (isset($filterVal[$key])) {
                                $filterVal[$key] = Date::parse($filterVal[$key]);
                            }
                        }
                    } else {
                        $filterVal = Date::parse($filterVal);
                    }
                }

                if ($filterDefinition) {
                    $this->processedFilters[] = [
                        'column' => $dataField,
                        'mode' => $filterDefinition->mode,
                        'type' => $filterDefinition->inputType,
                        'value' => $filterVal,
                    ];

                    $this->appliedFilters[] = [
                        'wire-name' => $filterDefinition->buildWireModelAttribute($this->filtersUrlParam()),
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

    protected function getFilterValue(Filter $filterDefinition): mixed
    {
        if (isset($this->filters[$filterDefinition->dataField][$filterDefinition->name])) {
            return $this->filters[$filterDefinition->dataField][$filterDefinition->name];
        }

        return null;
    }

    protected function getTableData()
    {
        if (!$this->dataTable->dataSrc) {
            return [];
        }

        $params = new LwDataRetrievalParams(
            page: Paginator::resolveCurrentPage($this->dataTable->pageName),
            perPage: $this->perPage,
            pageName: $this->dataTable->pageName,
            search: $this->search,
            searchDataFields: $this->dataTable?->search->dataFields ?? [],
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
            //$this->dataProviderIsClass() => $this->getDataUsingClassObject($params),
            $this->dataProviderIsCallable() => $this->getDataUsingCallable($params),
            default => throw new \LogicException("Cannot get data from [{$this->dataTable->dataSrc}]")
        };

        // process query string $data and set it on the DataTable object
        //return $this->executeCallable($this->dataTable->dataSrc, ...$params);
    }

    protected function getDataUsingDataProviderObject(LwDataRetrievalParams $params): LengthAwarePaginator
    {
        /** @var ProvidesDataTableData */
        $dataProvider = app()->make($this->dataTable->dataSrc);

        return $dataProvider->dataTableData($params);
    }

    protected function getDataUsingDataTableQuery(LwDataRetrievalParams $params): LengthAwarePaginator
    {
        /** @var BuildsDataTableQuery */
        $queryProvider = App::make($this->dataTable->dataSrc);

        $query = $queryProvider->buildLwDataTableQuery($params);

        return $this->getDataFromQuery($query, $params->page, $params->perPage, $params->pageName);
    }

    protected function getDataFromQuery(QueryBuilder|EloquentBuilder $query, int $page, int $perPage, string $pageName): CursorPaginator|LengthAwarePaginator|Paginator
    {
        return match ($this->dataTable->dataSrcPagination) {
            static::PAGINATION_LENGTH_AWARE => $query->paginate(perPage: $perPage, pageName: $pageName, page: $page),
            static::PAGINATION_CURSOR => $query->cursorPaginate(perPage: $perPage, cursorName: $pageName),
            static::PAGINATION_SIMPLE => $query->simplePaginate(perPage: $perPage, pageName: $pageName, page: $page),
        };
    }

    protected function getDataUsingEloquenModel(LwDataRetrievalParams $params)
    {
        return (new EloquentDataSource($this->dataTable))->getData($params);
    }

    // protected function getDataUsingClassObject(LwDataRetrievalParams $params)
    // {
    //     //
    //     $dataProvideObject = App::make($this->dataTable->dataSrc);
    //     $dataProviderMethod = $this->dataTable->dataSrcGetDataMethod;

    //     return App::call([$dataProvideObject, $dataProviderMethod], ['params' => $params]);

    // }

    protected function getDataUsingCallable(LwDataRetrievalParams $params)
    {
        //
    }



    protected function dataProviderProvidesDataTableData(): bool
    {
        return \is_a($this->dataTable->dataSrc, ProvidesDataTableData::class, true);
    }

    protected function dataProviderBuildsDataTableQuery(): bool
    {
        return \is_a($this->dataTable->dataSrc, BuildsDataTableQuery::class, true);
    }

    protected function dataProviderIsEloquentModel(): bool
    {
        return \is_a($this->dataTable->dataSrc, EloquentModel::class, true);
    }

    protected function dataProviderIsClass(): bool
    {
        return \class_exists($this->dataTable->dataSrc);
    }

    protected function dataProviderIsCallable(): bool
    {
        if (\is_callable($this->dataTable->dataSrc)) {
            return true;
        }

        $callable = Str::parseCallback($this->dataTable->dataSrc);

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
                'as' => $this->searchUrlParam(),
            ],
            'filters' => [
                'as' => $this->filtersUrlParam(),
            ],
            'columnsSearch' => [
                'as' => $this->columnsSearchUrlParam(),
            ],
        ];
    }
}
