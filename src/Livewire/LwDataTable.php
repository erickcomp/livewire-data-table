<?php

namespace ErickComp\LivewireDataTable\Livewire;

use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Data\ProvidesUnpaginatedData;
use ErickComp\LivewireDataTable\Data\StaticDataDataSource;
use ErickComp\LivewireDataTable\DataTable;
use ErickComp\LivewireDataTable\DataTable\BulkAction;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\RowAction;
use ErickComp\LivewireDataTable\Selection\Selection;
use ErickComp\LivewireDataTable\Support\ActionResult;
use ErickComp\LivewireDataTable\Support\ActionResultHandler;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\Pagination\Paginator as CursorPaginationContract;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Js;
use Illuminate\Support\LazyCollection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
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

    #[Locked]
    /** @var ?StaticDataDataSource $s Static data passed to component, as arrays or collections*/
    public ?StaticDataDataSource $sd;

    #[Locked]
    public string $vd = '';

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

    /** One of the Selection::MODE_* constants. Only actions change it (selectAllMatching(), deselectAll()) */
    #[Locked]
    public string $selectionMode = Selection::MODE_IDS;

    /**
     * The ids of the rows picked in the "ids" selection mode, or of the ones left out in the "all" mode. The browser
     * changes it as rows are (un)checked, without a request: it travels with the next one.
     *
     * @var string[]
     */
    public array $selectedIds = [];

    /** How many rows the "all" selection mode covers, before exclusions */
    #[Locked]
    public ?int $matchingTotal = null;

    /**
     * The messages shown above the table, e.g. the result of a bulk action. They last until the next request.
     *
     * @var array<int, array{type: string, message: string, key: string}>
     */
    #[Locked]
    public array $flash = [];

    protected DataTable $dataTable;

    /**
     * Set when an update changes the search or the filters. The selection is dropped once all the updates of the request
     * are in: the same request may carry rows (un)checked meanwhile, applied after the search or before it.
     */
    protected bool $selectionResetPending = false;

    //protected string $preset = 'vanilla';
    protected array $processedFilters = [];
    protected array $appliedFilters = [];
    protected Preset $loadedPreset;

    public function __construct()
    {
        $this->dataTable ??= new DataTable();
    }

    public function render()
    {
        $this->applyPendingSelectionReset();
        $this->setupMaxMemory();
        $this->processFilters();
        //$this->setupSearchAttributes();
        //$this->setupFiltersAttributes();

        $rows = $this->getTableData();

        if ($rows instanceof LengthAwarePaginator && ($this->paginators[$rows->getPageName()] ?? 1) > $rows->lastPage()) {

            // Forces page reset to last page and re-evaluate the data
            $this->paginators[$rows->getPageName()] = $rows->lastPage();

            $rows = $this->getTableData();
        }

        // When the rows tell how many match, the count of the "all" selection is kept current for free
        if ($this->selectionMode === Selection::MODE_ALL && ($recordsTotal = $this->recordsTotal($rows)) !== null) {
            $this->matchingTotal = $recordsTotal;
        }

        $viewData = [
            'rows' => $rows,
            'initialFilters' => $this->computeInitialFilters(),
        ];

        return view()
            ->file(\substr(__FILE__, 0, -3) . 'blade.php')
            ->with($viewData);
    }

    public function preset(): Preset
    {
        if (!isset($this->loadedPreset)) {

            if (!isset($this->dataTable)) {
                return Preset::loadFromName('empty');
            }

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
            $rows instanceof Collection || $rows instanceof LazyCollection => '',
            $rows instanceof LengthAwarePaginatorContract => $rows->render($this->paginationView()),
            $rows instanceof PaginatorContract || $rows instanceof CursorPaginatorContract => $rows->render($this->paginationSimpleView()),
            \is_object($rows) && \method_exists($rows, 'links') => $rows->links($this->paginationView())
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

            clearAllFilters() {
                this.dtData().clearAllFilters(\$wire);
            },

            toggleFiltersContainer(event) {
                this.dtData().toggleFiltersContainer(\$wire);
            }{$this->selectionXData()}{$this->bulkActionsXData()}{$this->rowActionsXData()}
        };";

        return $xData;

        // $xData = \preg_replace('/\s+/', ' ', $xData);
        // $xData = \str_replace([' :', ': '], ':', $xData);
        // $xData = \str_replace([' ,', ', '], ',', $xData);

        // return \trim($xData);
    }

    /**
     * The Alpine methods behind the row selection, added to xData() when rows can be selected
     */
    protected function selectionXData(): string
    {
        if (!$this->dataTable->selectable) {
            return '';
        }

        $selectAllMode = Selection::MODE_ALL;

        return ",

            /* Row selection: \$wire.selectedIds lists the selected rows, or the ones left out in the 'all' mode. Changes
               to it travel with the next request. */
            isRowSelected(id) {
                const listed = \$wire.selectedIds.includes(id);

                return \$wire.selectionMode === '{$selectAllMode}' ? !listed : listed;
            },

            toggleRow(id) {
                this.setRowsSelected([id], !this.isRowSelected(id));
            },

            selectPage(ids) {
                this.setRowsSelected(ids, true);
            },

            deselectPage(ids) {
                this.setRowsSelected(ids, false);
            },

            setRowsSelected(ids, selected) {
                const listed = \$wire.selectionMode === '{$selectAllMode}' ? !selected : selected;
                const others = \$wire.selectedIds.filter(id => !ids.includes(id));

                \$wire.selectedIds = listed ? [...others, ...ids] : others;
            },

            pageSelectionState(ids) {
                const selected = ids.filter(id => this.isRowSelected(id)).length;

                return selected === 0 ? 'none' : (selected === ids.length ? 'all' : 'some');
            },

            get selectedCount() {
                return \$wire.selectionMode === '{$selectAllMode}'
                    ? Math.max((\$wire.matchingTotal ?? 0) - \$wire.selectedIds.length, 0)
                    : \$wire.selectedIds.length;
            },

            selectAllMatching() {
                \$wire.selectAllMatching();
            },

            deselectAll() {
                if (\$wire.selectionMode === '{$selectAllMode}') {
                    \$wire.deselectAll();
                } else {
                    \$wire.selectedIds = [];
                }
            }";
    }

    /**
     * The Alpine method behind the bulk actions dropdown, added to xData() when there are bulk actions
     */
    protected function bulkActionsXData(): string
    {
        if (!$this->dataTable->hasBulkActions()) {
            return '';
        }

        $noSelectionMessage = Js::from(__('erickcomp_lw_data_table::messages.bulk_actions_no_selection'));
        $confirmFunction = $this->confirmDialogConfig()['function-name'];

        return ",

            /* Runs the action picked in the dropdown (an option with data-lw-dt-* attributes) on the selected rows */
            async runBulkAction(select) {
                const picked = select.selectedOptions[0];
                select.value = '';

                if (!picked || !picked.dataset.lwDtAction) {
                    return;
                }

                if (this.selectedCount === 0) {
                    window.alert({$noSelectionMessage});

                    return;
                }

                if (picked.dataset.lwDtConfirm !== undefined) {
                    const message = picked.dataset.lwDtConfirm.replaceAll(':count', this.selectedCount);

                    if (!(await {$confirmFunction}(message, picked.dataset.lwDtConfirmTitle))) {
                        return;
                    }
                }

                await \$wire.executeBulkAction(picked.dataset.lwDtAction, picked.dataset.lwDtOption ?? null);
            }";
    }

    /**
     * The Alpine methods behind the row actions and the row click, added to xData() when the table has them. The
     * actions are elements with data-lw-dt-* attributes: a link, a form (any other method than GET) or a button that
     * runs the action on the server.
     */
    protected function rowActionsXData(): string
    {
        if (!$this->dataTable->hasRowActions() && !$this->dataTable->hasRowClick()) {
            return '';
        }

        $confirmFunction = $this->confirmDialogConfig()['function-name'];

        return ",

            async confirmRowAction(el) {
                if (el.dataset.lwDtConfirm === undefined) {
                    return true;
                }

                return await {$confirmFunction}(el.dataset.lwDtConfirm, el.dataset.lwDtConfirmTitle);
            },

            async followRowLink(link) {
                if (!(await this.confirmRowAction(link))) {
                    return;
                }

                if (link.target && link.target !== '_self') {
                    window.open(link.href, link.target, 'noopener');
                } else {
                    window.location.href = link.href;
                }
            },

            async submitRowActionForm(form) {
                if (await this.confirmRowAction(form)) {
                    form.submit();
                }
            },

            async runRowAction(button) {
                if (await this.confirmRowAction(button)) {
                    await \$wire.executeRowAction(button.dataset.lwDtRowAction, button.dataset.lwDtRowId);
                }
            },

            /* A click on a row runs its row-click action (by clicking the action's own element) or follows its
               row-click-url. Clicks on links, buttons, inputs and the like do what they do instead. */
            onRowClick(event) {
                const row = event.currentTarget;
                const ignored = event.target.closest('a, button, input, select, textarea, label, summary, [data-lw-dt-no-row-click]');

                if ((ignored && row.contains(ignored)) || window.getSelection().toString() !== '') {
                    return;
                }

                if (row.dataset.lwDtRowClickUrl !== undefined) {
                    if (event.ctrlKey || event.metaKey) {
                        window.open(row.dataset.lwDtRowClickUrl, '_blank', 'noopener');
                    } else {
                        window.location.href = row.dataset.lwDtRowClickUrl;
                    }

                    return;
                }

                row.querySelector(`[data-lw-dt-row-action='\${CSS.escape(row.dataset.lwDtRowClickAction)}']`)?.click();
            }";
    }

    /**
     * The preset's "confirm-dialog": the JS function asked to confirm an action, by default window.confirm()
     *
     * @return array{function-name: string, function-code: ?string}
     */
    protected function confirmDialogConfig(): array
    {
        $config = $this->preset()->get('confirm-dialog', null) ?? [];

        if (($config['function-name'] ?? null) === null) {
            return [
                'function-name' => 'lwDataTableConfirm',
                'function-code' => <<<JS
                    async function lwDataTableConfirm(message, title) {
                        return window.confirm(message);
                    }
                JS,
            ];
        }

        return [
            'function-name' => $config['function-name'],
            'function-code' => $config['function-code'] ?? null,
        ];
    }

    public function updating(string $property, $value)
    {
        if (\in_array($property, ['search', 'filters', 'perPage']) || \str_starts_with($property, 'columnsSearch.')) {
            $this->resetPage($this->pageNameUrlParam());
        }

        if (\in_array($property, ['search', 'filters', 'columnsSearch']) || \str_starts_with($property, 'filters.') || \str_starts_with($property, 'columnsSearch.')) {
            $this->selectionResetPending = true;
        }
    }

    /**
     * Selects every row matching the current search and filters, across all pages. Rows unchecked afterwards are
     * listed in $selectedIds as exclusions.
     */
    public function selectAllMatching(): void
    {
        if (!$this->dataTable->selectable) {
            return;
        }

        $this->applyPendingSelectionReset();

        $this->selectionMode = Selection::MODE_ALL;
        $this->selectedIds = [];
        $this->matchingTotal = $this->makeSelection(Selection::MODE_ALL)->count();
    }

    public function deselectAll(): void
    {
        $this->selectionMode = Selection::MODE_IDS;
        $this->selectedIds = [];
        $this->matchingTotal = null;
    }

    /**
     * Runs a bulk action (<x-data-table.bulk-action>) on the selected rows. The browser only sends the action's name
     * and option: everything else comes from the table's definition and is checked here.
     */
    public function executeBulkAction(string $name, ?string $option = null): mixed
    {
        $action = $this->dataTable->bulkActions?->find($name);

        if ($action === null || ($action->hasOptions() ? !$action->hasOption($option) : $option !== null)) {
            $this->flashMessage(ActionResult::error(__('erickcomp_lw_data_table::messages.action_unavailable')));

            return null;
        }

        if ($action->can !== null) {
            Gate::authorize($action->can);
        }

        $selection = $this->selection();
        $count = $selection->count();

        if ($count === 0) {
            $this->flashMessage(ActionResult::error(__('erickcomp_lw_data_table::messages.bulk_actions_no_selection')));

            return null;
        }

        if ($action->event !== null) {
            $this->dispatch($action->event, action: $action->name, option: $option, ids: $selection->ids(), count: $count);

            if (!$action->keepSelection) {
                $this->deselectAll();
            }

            return null;
        }

        $result = ActionResultHandler::run(
            $action->handler,
            [
                'selection' => $selection,
                Selection::class => $selection,
                'option' => $option,
                'action' => $action,
                BulkAction::class => $action,
            ],
            $this,
            __('erickcomp_lw_data_table::messages.bulk_action_done'),
            [':count' => (string) $count],
        );

        if ($result->message !== null) {
            $this->flashMessage($result->message);
        }

        if (!$result->failed() && !$action->keepSelection) {
            $this->deselectAll();
        }

        return $result->response;
    }

    /**
     * Runs a row action (<x-data-table.row-action>) with a handler or an event on a row. The browser only sends the
     * action's name and the row's id: the row must be among the table's rows (with the current search and filters),
     * and the user must pass the action's gate with it.
     */
    public function executeRowAction(string $name, string $rowId): mixed
    {
        $action = $this->dataTable->findRowAction($name);

        if ($action === null || $action->isLink()) {
            $this->flashMessage(ActionResult::error(__('erickcomp_lw_data_table::messages.action_unavailable')));

            return null;
        }

        $selection = $this->makeSelection(Selection::MODE_IDS, [$rowId]);
        $row = $selection->rows()->first();

        if ($row === null) {
            $this->flashMessage(ActionResult::error(__('erickcomp_lw_data_table::messages.row_not_found')));

            return null;
        }

        if ($action->can !== null) {
            // Wrapped: an array row would be taken for a list of arguments
            Gate::authorize($action->can, [$row]);
        }

        if ($action->event !== null) {
            $this->dispatch($action->event, action: $action->name, id: $rowId);

            return null;
        }

        $parameters = [
            'row' => $row,
            'id' => $rowId,
            'selection' => $selection,
            Selection::class => $selection,
            'action' => $action,
            RowAction::class => $action,
        ];

        // A model (or any object) row can also be asked for by its class, e.g. "User $user"
        if (\is_object($row)) {
            $parameters[$row::class] = $row;
        }

        $result = ActionResultHandler::run(
            $action->handler,
            $parameters,
            $this,
            __('erickcomp_lw_data_table::messages.row_action_done'),
        );

        if ($result->message !== null) {
            $this->flashMessage($result->message);
        }

        return $result->response;
    }

    /**
     * The messages last for one response: any later request (paging, sorting, another action...) drops them
     */
    public function hydrate(): void
    {
        $this->flash = [];
    }

    /**
     * $selectedIds is written by the browser, possibly one index at a time within a request: it is cleaned up once
     * all the updates and actions of the request are done.
     */
    public function dehydrate(): void
    {
        $this->applyPendingSelectionReset();

        $this->selectedIds = $this->normalizedSelectedIds();
    }

    /**
     * Keeps columnsSearch free of empty entries (e.g., a frontend x-mask syncing an
     * empty value during component mounting)—without this, the key remains in the array
     * and #[Url] causes "cols-search[field]=" to appear in the URL indefinitely,
     * even when no actual search is applied. 
     */
    public function updated(string $property, $value)
    {
        if (!\str_starts_with($property, 'columnsSearch.')) {
            return;
        }

        $dataField = \substr($property, \strlen('columnsSearch.'));

        if ($value === null || $value === '' || $value === []) {
            unset($this->columnsSearch[$dataField]);
        }
    }

    public function withHtmlAttributes(array $attributes): static
    {
        // The DataTable is mounted by LwDataTableComponentHook; it must not end up as an attribute of the root element
        unset($attributes['data-table'], $attributes['dataTable']);

        return parent::withHtmlAttributes($attributes);
    }

    public function setSortBy(string $dataField, ?string $sortDir = null)
    {
        $this->resetPage($this->pageNameUrlParam());

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

        $this->resetSelectionAfterFilterChange();
    }

    /**
     * Drops the search and all the filters at once (the "Clear all" button next to the active filters)
     */
    public function clearAllFilters(): void
    {
        $this->search = '';
        $this->filters = [];
        $this->rawFilters = [];

        $this->resetPage($this->pageNameUrlParam());
        $this->resetSelectionAfterFilterChange();
    }

    /**
     * Re-renders the table, e.g. after its rows were changed elsewhere on the page:
     *
     *     Livewire.dispatch('lw-dt-refresh', { table: 'products' })   // in the browser
     *     $this->dispatch('lw-dt-refresh', table: 'products');        // in another Livewire component
     *
     * Without a table, every table on the page re-renders; with one, only the table with that id or name does.
     */
    #[On('lw-dt-refresh')]
    public function refreshTable(?string $table = null): void
    {
        if ($table !== null && !\in_array($table, [$this->dataTable->id, $this->dataTable->name], true)) {
            $this->skipRender();

            return;
        }

        // Rows may have come or gone: render() only recounts them when the rows tell their total
        if ($this->selectionMode === Selection::MODE_ALL) {
            $this->matchingTotal = $this->makeSelection(Selection::MODE_ALL)->count();
        }
    }

    public function computeInitialFilters()
    {
        $fullFilters = [];
        foreach ($this->dataTable->filters?->filtersItems ?? [] as $filterItem) {
            $filterValue = $this->filters[$filterItem->dataField][$filterItem->name]
                ?? static::emptyFilterInputValue($filterItem);

            $fullFilters[$filterItem->dataField][$filterItem->name] = $filterValue;
        }

        return $fullFilters;
    }

    /**
     * The filter inputs with no value, as computeInitialFilters() shapes them
     */
    protected function emptyFilterInputs(): array
    {
        $emptyFilters = [];

        foreach ($this->dataTable->filters->filtersItems ?? [] as $filterItem) {
            $emptyFilters[$filterItem->dataField][$filterItem->name] = static::emptyFilterInputValue($filterItem);
        }

        return $emptyFilters;
    }

    protected static function emptyFilterInputValue(Filter $filter): string|array
    {
        return $filter->mode === Filter::MODE_RANGE ? ['from' => '', 'to' => ''] : '';
    }

    public function shouldShowFiltersContainer(): bool
    {
        $isCollapsible = $this->dataTable->filters?->isCollapsible() ?? false;

        if (!$isCollapsible) {
            return true;
        }

        if (\is_bool($this->filtersContainerIsOpen)) {
            return $this->filtersContainerIsOpen;
        }

        return true;

        // if (!$this->dataTable->filters?->isCollapsible() ?? false) {
        //     return true;
        // }

        // if (\is_bool($this->filtersContainerIsOpen)) {
        //     return $this->filtersContainerIsOpen;
        // }

        // return !empty($this->filters);
    }

    public function isDataPaginated($rows): bool
    {
        return $rows instanceof CursorPaginationContract || $rows instanceof CursorPaginatorContract;
    }

    public function shouldAllowColumnsSearch($rows): bool
    {
        return $this->hasMoreThanOneRow($rows);
    }

    public function shouldAllowSorting($rows): bool
    {
        return $this->hasMoreThanOneRow($rows);
    }

    public function hasRows($rows): bool
    {
        return ($rows instanceof LazyCollection ? $rows->take(1)->count() === 1 : $rows->count() > 0);
    }

    protected function hasMoreThanOneRow($rows): bool
    {
        return ($rows instanceof LazyCollection ? $rows->take(2)->count() === 2 : $rows->count() > 1);
    }

    protected function mountDataTable(DataTable $dataTable)
    {
        if ($dataTable->hasStaticDataSource()) {
            $this->sd = $dataTable->dataSrc;
        } else {
            unset($this->sd);
        }

        if (!empty($dataTable->dynamicViewData)) {
            $this->vd = \encrypt(\serialize($dataTable->dynamicViewData));
        }

        // Fails early (and loudly) on a row-click naming a row action that does not exist
        $dataTable->rowClickAction();

        $this->dataTable = $dataTable;

        $this->dt = DataTable::toCache($dataTable);
    }

    protected function hydrateDataTable()
    {
        $dataTable = DataTable::fromCache($this->dt ?? '', $this->sd ?? null);

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

        if (!empty($this->vd)) {
            $dataTable->dynamicViewData = \unserialize(\decrypt($this->vd));
        }

        $this->dataTable = $dataTable;

        return null;
    }

    protected function pageNameUrlParam(): string
    {
        return $this->dataTable->pageName
            ?? $this->preset()->get('query-string-page-name')
            ?? \config('erickcomp-livewire-data-table.query-string-page-name', 'page');
    }

    public function filtersUrlParam(): string
    {
        return $this->dataTable->filtersName
            ?? $this->preset()->get('query-string-filters')
            ?? \config('erickcomp-livewire-data-table.query-string-filters', 'filters');
    }

    protected function searchUrlParam(): string
    {
        return $this->dataTable->searchName
            ?? $this->preset()->get('query-string-search')
            ?? \config('erickcomp-livewire-data-table.query-string-search', 'search');
    }

    protected function columnsSearchUrlParam(): string
    {
        return $this->dataTable->columnsSearchName
            ?? $this->preset()->get('query-string-cols-search')
            ?? \config('erickcomp-livewire-data-table.query-string-param-cols-search', 'cols-search');
    }

    protected function setupMaxMemory()
    {
        if (!isset($this->dataTable)) {
            return;
        }

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

                if ($filterDefinition) {
                    $isRangeMode = $filterDefinition->mode === Filter::MODE_RANGE;

                    $isSelectFilter = $filterDefinition->inputType === Filter::TYPE_SELECT;
                    $isMultiSelectFilter = $filterDefinition->inputType === Filter::TYPE_SELECT_MULTIPLE;

                    // Filtros select com custom renderer não recebem a prop 'options', então
                    // precisamos renderizar o Blade e extrair as options do HTML antes de montar
                    // os labels dos filtros aplicados. O resultado é cacheado no Filter para que
                    // a view não precise re-renderizar — basta chamar o mesmo método novamente.
                    if (
                        ($isSelectFilter || $isMultiSelectFilter)
                        && !empty($filterDefinition->customRendererCode)
                        && empty($filterDefinition->getSelectOptions())
                    ) {
                        $filterDefinition->getCustomRendererCodeWithXModel(
                            'inputFilters',
                            \array_merge($this->dataTable->allViewData(), ['___lwDataTable' => $this]),
                        );
                    }

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

                    $this->processedFilters[] = [
                        'column' => $dataField,
                        'mode' => $filterDefinition->mode,
                        'type' => $filterDefinition->inputType,
                        'value' => $filterVal,
                    ];

                    $this->appliedFilters[] = [
                        'wire-name' => $filterDefinition->buildWireModelAttribute($this->filtersUrlParam()),
                        'removal-key' => $filterDefinition->buildInputNameAttribute($this->filtersUrlParam()),
                        'name' => $filterDefinition->name,
                        'label' => value(
                            function ($string) use ($filterDefinition, $filterVal, $isRangeMode, $isSelectFilter, $isMultiSelectFilter) {
                                if ($isRangeMode) {
                                    return $string->append(($filterVal['from'] ?? '...') . ' - ' . ($filterVal['to'] ?? '...'));
                                }

                                if ($isSelectFilter) {
                                    $label = '"' . ($filterDefinition->getSelectOptions()["$filterVal"] ?? $filterVal) . '"';

                                    return $string->append($label);
                                }

                                if ($isMultiSelectFilter) {
                                    $labels = [];
                                    foreach ($filterVal as $fv) {
                                        $labels[] = '"' . ($filterDefinition->getSelectOptions()[$fv] ?? $fv) . '"';

                                    }
                                    $strLabels = Arr::join($labels, ', ', __('erickcomp_lw_data_table::messages.multi_select_filter_label_final_glue'));

                                    return $string->append($strLabels);

                                }
                                return $string->append((string) $filterVal);
                            },
                            str($filterDefinition->label . ': '),
                        )->toString(),
                    ];
                }
            }
        }
    }

    public function getFilterValue(Filter $filterDefinition): mixed
    {
        if (isset($this->filters[$filterDefinition->dataField][$filterDefinition->name])) {
            return $this->filters[$filterDefinition->dataField][$filterDefinition->name];
        }

        return null;
    }

    protected function getTableData()
    {
        if (!isset($this->dataTable) || !$this->dataTable->dataSrc) {
            return [];
        }

        return $this->dataTable->dataSrc->getData($this->buildRetrievalParams());
    }

    /**
     * The current search, filters, columns search, sorting and page, as handed to the data source. The filters are
     * the ones processFilters() left in $processedFilters.
     */
    protected function buildRetrievalParams(): LwDataRetrievalParams
    {
        return new LwDataRetrievalParams(
            page: $this->currentPage(),
            perPage: $this->perPage,
            pageName: $this->pageNameUrlParam(),
            search: $this->search,
            //searchDataFields: $this->dataTable?->search->dataFields ?? [],
            columnsSearch: $this->sanitizeColumnsSearch($this->columnsSearch),
            filters: $this->processedFilters,
            sortBy: $this->sanitizeSortBy($this->sortBy),
            sortDir: $this->sortDir,
            collectionsSortingFlags: $this->dataTable->collectionSortingFlags,
            dataTable: $this->dataTable,
        );
    }

    /**
     * The current page number, or null under cursor pagination. Livewire keeps the cursor in the same paginator key
     * as page numbers, and its page resolver replaces any value that is not a page number with 1: resolving the page
     * would send a cursor paginated table back to its first page.
     */
    protected function currentPage(): ?int
    {
        if ($this->dataTable->dataSrcPaginationType === DataSourcePaginationType::Cursor) {
            return null;
        }

        $pageName = $this->pageNameUrlParam();
        $page = $this->paginators[$pageName] ?? null;

        if (\is_string($page) && $page !== '' && \filter_var($page, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        return Paginator::resolveCurrentPage($pageName);
    }

    /**
     * The rows picked with the given ids (see Selection), resolved against the data source with the table's current
     * search, filters and columns search. Not public on purpose: Livewire lets the browser call public methods.
     *
     * @param string $mode One of the Selection::MODE_* constants
     * @param iterable<mixed> $ids The selected ids in the "ids" mode, the excluded ones in the "all" mode
     */
    protected function makeSelection(string $mode, iterable $ids = []): Selection
    {
        $dataSource = $this->dataTable->dataSrc;

        if (!$dataSource instanceof ProvidesUnpaginatedData) {
            throw new \LogicException('Cannot select rows from a data source of type [' . \get_debug_type($dataSource) . ']: it does not implement [' . ProvidesUnpaginatedData::class . ']');
        }

        // An action may run before render(), the only other place filters are processed
        $this->processFilters();

        return new Selection($dataSource, $this->buildRetrievalParams(), $this->dataTable->dataIdentityColumn, $mode, $ids);
    }

    /**
     * The rows the user picked. Not public on purpose (see makeSelection()): actions get it injected.
     */
    protected function selection(): Selection
    {
        $this->applyPendingSelectionReset();

        return $this->makeSelection($this->selectionMode, $this->normalizedSelectedIds());
    }

    protected function flashMessage(ActionResult $message): void
    {
        // The key tells the messages apart when the page morphs: a new one is never taken for one dismissed before
        $this->flash[] = [...$message->toArray(), 'key' => \bin2hex(\random_bytes(4))];
    }

    /**
     * The bulk actions the current user may run: the ones whose gate ability is denied are left out of the dropdown
     *
     * @return BulkAction[]
     */
    protected function availableBulkActions(): array
    {
        if (!$this->dataTable->hasBulkActions()) {
            return [];
        }

        return \array_filter(
            $this->dataTable->bulkActions->actions,
            fn(BulkAction $action) => $action->can === null || Gate::allows($action->can),
        );
    }

    /**
     * The row actions the current user may run on the row: the ones whose gate denies it are left out
     *
     * @return RowAction[]
     */
    protected function visibleRowActions(mixed $row): array
    {
        if (!$this->dataTable->hasRowActions()) {
            return [];
        }

        return \array_filter(
            $this->dataTable->rowActions->actions,
            // Wrapped: an array row would be taken for a list of arguments
            fn(RowAction $action) => $action->can === null || Gate::allows($action->can, [$row]),
        );
    }

    /**
     * What makes a row clickable (row-click or row-click-url), or nothing when it is not: a row-click action must be
     * among the row's visible actions
     *
     * @param RowAction[] $visibleRowActions
     * @return array<string, string>
     */
    protected function rowClickAttributes(mixed $row, string $rowId, array $visibleRowActions): array
    {
        if ($this->dataTable->rowClickUrl !== null) {
            $target = ['data-lw-dt-row-click-url' => RowAction::fillUrl($this->dataTable->rowClickUrl, $row, 'the [row-click-url] of the table')];
        } elseif ($this->dataTable->rowClick !== null && isset($visibleRowActions[$this->dataTable->rowClick])) {
            $target = ['data-lw-dt-row-click-action' => $this->dataTable->rowClick];
        } else {
            return [];
        }

        $clickableClass = Arr::toCssClasses($this->preset()->get('table.tbody.tr.clickable.class', []));

        return \array_filter([
            'data-lw-dt-row-id' => $rowId,
            ...$target,
            'x-on:click' => 'onRowClick($event)',
            'class' => $clickableClass === '' ? null : $clickableClass,
        ], fn(?string $value) => $value !== null);
    }

    protected function selectedCount(): int
    {
        return $this->selection()->count();
    }

    /**
     * @return string[]
     */
    protected function normalizedSelectedIds(): array
    {
        $maxSelectedIds = (int) \config('erickcomp-livewire-data-table.max-selected-ids', 10000);

        return \array_slice(Selection::normalizeIds($this->selectedIds), 0, \max($maxSelectedIds, 0));
    }

    /**
     * Rows picked under other search terms or filters may no longer be among the table's rows: the selection is
     * dropped, as in Magento's grids. With preserve-selection-on-filter, rows picked one by one stay selected (the
     * data source still leaves out the ones that no longer match); a selection of "all" the rows always goes, since
     * "all" now means other rows.
     */
    protected function applyPendingSelectionReset(): void
    {
        if ($this->selectionResetPending) {
            $this->selectionResetPending = false;
            $this->resetSelectionAfterFilterChange();
        }
    }

    protected function resetSelectionAfterFilterChange(): void
    {
        if ($this->selectionMode === Selection::MODE_IDS && $this->dataTable->preserveSelectionOnFilter) {
            return;
        }

        $this->deselectAll();
    }

    /**
     * The identity of each row on the current page, as the browser refers to them
     *
     * @return string[]
     */
    protected function pageRowsIds(mixed $rows): array
    {
        $rows = $rows instanceof PaginatorContract || $rows instanceof CursorPaginatorContract
            ? $rows->items()
            : $rows;

        $ids = [];

        foreach ($rows as $row) {
            $ids[] = $this->rowSelectionId($row);
        }

        return Selection::normalizeIds($ids);
    }

    protected function rowSelectionId(mixed $row): string
    {
        $id = \data_get($row, $this->dataTable->dataIdentityColumn);

        return \is_scalar($id) || $id instanceof \Stringable ? (string) $id : '';
    }

    /**
     * How many rows match the current search and filters, when the rows tell (a length-aware paginator, or all the
     * rows at once). Simple and cursor paginators do not know it.
     */
    protected function recordsTotal(mixed $rows): ?int
    {
        return match (true) {
            $rows instanceof LengthAwarePaginatorContract => $rows->total(),
            $rows instanceof Collection, $rows instanceof LazyCollection => $rows->count(),
            \is_array($rows) => \count($rows),
            default => null,
        };
    }

    protected function sanitizeSortBy(string $sortBy): string
    {
        if ($sortBy === '') {
            return '';
        }

        $sortableFields = $this->dataTable->columns
            ->filter(fn($col) => $col->isSortable() && $col->dataField !== null)
            ->pluck('dataField')
            ->all();

        return \in_array($sortBy, $sortableFields, true) ? $sortBy : '';
    }

    protected function sanitizeColumnsSearch(array $columnsSearch): array
    {
        if (empty($columnsSearch)) {
            return [];
        }

        $searchableFields = $this->dataTable->columns
            ->filter(fn($col) => $col->isSearchable() && $col->dataField !== null)
            ->pluck('dataField')
            ->all();

        return \array_intersect_key($columnsSearch, \array_flip($searchableFields));
    }

    protected function queryString()
    {
        return [
            // 'page' => [
            //     'as' => $this->pageNameUrlParam(),
            // ],
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
