<?php

namespace ErickComp\LivewireDataTable;

use ErickComp\LivewireDataTable\Builders\Column\BaseColumn;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use ErickComp\LivewireDataTable\Data\DataSourceFactory;
use ErickComp\LivewireDataTable\Data\EloquentDataSource;
use ErickComp\LivewireDataTable\Data\StaticDataDataSource;
use ErickComp\LivewireDataTable\DataTable\BaseDataTableComponent;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\CustomRenderedColumn;
use ErickComp\LivewireDataTable\DataTable\DataColumn;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Filters;
use ErickComp\LivewireDataTable\DataTable\Footer;
use ErickComp\LivewireDataTable\DataTable\Search;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use ErickComp\LivewireDataTable\Livewire\Preset;
use ErickComp\LivewireDataTable\Src\Drawer\DataTableActionResponse;
use ErickComp\LivewireDataTable\Src\Drawer\ErrorMessageForUserException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\Component as BladeComponent;
use Illuminate\View\ComponentAttributeBag;
use Livewire\ImplicitlyBoundMethod;
use Livewire\Wireable;
use ErickComp\LivewireDataTable\Data\DataSource;
use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
//use ErickComp\LivewireDataTable\Builders\Column\DataColumn;

class DataTable extends BaseDataTableComponent //implements Wireable
{
    use FillsComponentAttributeBags;

    public DataSource $dataSrc;

    public DataSourcePaginationType $dataSrcPaginationType = DataSource::PAGINATION_DEFAULT;
    public const PER_PAGE_MAX = 'max';
    public const PER_PAGE_ALL = 'all';
    public const PER_PAGE_ALL_OPTION_VALUE = '___all___';

    public ?string $paginationView = null;
    public ?string $paginationCode = null;
    public ?string $lengthAwarePaginationView = null;
    public ?string $simplePaginationView = null;
    public array $perPageOptions = [];
    public static string $defaultPaginationView = 'livewire::bootstrap';
    public static string $defaultPaginationSimpleView = 'livewire::simple-bootstrap';
    public static ?bool $useDefaultPaginationStylingForDefaultPaginationViews = true;

    //protected array $defaultContainerAttributes = ['class' => 'lw-dt-container'];
    //protected array $defaultTableAttributes = [];
    //protected array $defaultTheadAttributes = [];
    //protected array $defaultTheadTrAttributes = [];
    //protected array $defaultTheadSearchTrAttributes = [];
    //protected array $defaultTheadSearchThAttributes = [];
    //protected array $defaultThAttributes = [];
    //protected array $defaultTbodyAttributes = [];
    //protected array $defaultTbodyTrAttributes = [];

    //protected string $trAttributesModifierCode = '';

    protected string $rowLevelClassCode;
    protected string $rowLevelStyleCode;
    protected string $rowLevelAttributesCode;

    protected string $rowLevelClassCodePath;
    protected string $rowLevelStyleCodePath;
    protected string $rowLevelAttributesCodePath;

    protected string $searchRendererCode;
    protected ComponentAttributeBag $searchRendererCodeAttributes;
    //public bool $noStyles = false;
    public string $dataIdentityColumn = 'id';
    //public string $sortingClassPrefix = 'lw-dt-sort';

    public int $columnsSearchDebounce;
    public ComponentAttributeBag $containerAttributes;
    public ComponentAttributeBag $tableAttributes;
    public ComponentAttributeBag $theadAttributes;
    public ComponentAttributeBag $theadTrAttributes;
    public ComponentAttributeBag $theadSearchTrAttributes;
    public ComponentAttributeBag $theadSearchThAttributes;
    public ComponentAttributeBag $thAttributes;
    public ComponentAttributeBag $tbodyAttributes;
    public ComponentAttributeBag $tbodyTrAttributes;
    //public ComponentAttributeBag $tbodyTdAttributes;
    //public ComponentAttributeBag $tfootAttributes;
    //public ComponentAttributeBag $tfootTrAttributes;
    //public ComponentAttributeBag $tfootTdAttributes;

    //public iterable $rows = [];
    public ?Filters $filters = null;
    public ?Footer $footer = null;

    // public ?string $name {
    //     get {
    //         if ($this->tableAttributes->has('name')) {
    //             $name = $this->tableAttributes['name'];

    //             if (!empty(\trim($name))) {
    //                 return $name;
    //             }
    //         }

    //         return null;
    //     }
    // }

    // public ?string $id {
    //     get {
    //         if ($this->tableAttributes->has(key: 'id')) {
    //             $id = $this->tableAttributes['id'];

    //             if (!empty(\trim($id))) {
    //                 return $id;
    //             }
    //         }

    //         return null;
    //     }
    // }

    /** @var string[] $assets */
    public array $assets = [];

    /** @var string[] $scripts */
    public array $scripts = [];
    public ?Search $search = null;

    //public array|true $searchable;
    protected Preset $loadedPreset;

    /** @var Collection<Column> */
    public Collection $columns;

    public function __construct(
        public string $preset = 'empty',

        // Data
        string|iterable|Collection|EloquentBuilder|QueryBuilder|Paginator|LengthAwarePaginator|CursorPaginator|callable|null $dataSrc = null,
        public ?string $dataSrcPagination = DataSource::PAGINATION_DEFAULT->value,
        string|array $perPage = [],
        public ?string $pageName = null,
        public ?string $searchName = null,
        public ?string $filtersName = null,
        public ?string $columnsSearchName = null,
        public int $maxPerPage = 1000,
        ?string $paginationView = null,
        public ?string $phpMaxMemory = null,

        //public ?string $dataProviderGetDataMethod = 'dataTable',

        //public bool $withoutSortingIndicators = false,

        // Columns 
        /** @var Column[] */
        iterable $columns = [],
        //public array $filters = [],
        //public string|false $search = false,
        //public array $columnsSearch = [],
        ?int $columnsSearchDebounce = null,


        public array $actions = [],

        // Rows
        ?string $rowLevelClassCode = null,
        ?string $rowLevelStyleCode = null,
        ?string $rowLevelAttributesCode = null,
    ) {

        $this->dataSrc = DataSourceFactory::new()->make($dataSrc, $this->dataSrcPaginationType);
        //$this->dataSrc = $dataSrc;
        //$this->dataProviderGetDataMethod = $dataProviderGetDataMethod;
        $this->paginationView = $paginationView;

        if (\is_string($perPage)) {
            $perPage = \array_filter(\array_map(trim(...), \explode(',', $perPage)));
        }

        if (empty($perPage)) {
            $perPage = $this->getDefaultPerPageOptions();
        }

        if ($rowLevelClassCode !== null) {
            $this->rowLevelClassCode = \html_entity_decode($rowLevelClassCode);
        }

        if ($rowLevelStyleCode !== null) {
            $this->rowLevelStyleCode = \html_entity_decode($rowLevelStyleCode);
        }

        if ($rowLevelAttributesCode !== null) {
            $this->rowLevelAttributesCode = \html_entity_decode($rowLevelAttributesCode);
        }

        $this->columns = collect($columns);

        //$this->columnsSearchDebounce = $columnsSearchDebounce
        //    ?? Preset::loadFromName(
        //        $this->preset,
        //    )->get(
        //            'columns-search-debounce-ms',
        //            \config('erickcomp-livewire-data-table.columns-search-debounce-ms', 200),
        //        );
        $this->columnsSearchDebounce = $columnsSearchDebounce ?? $this->preset()->get(
            'columns-search-debounce-ms',
            \config('erickcomp-livewire-data-table.columns-search-debounce-ms', 200),
        );

        $this->perPageOptions = $perPage;

        // $this->searchable = match (true) {
        //     $searchable === false => [],
        //     \is_string($searchable) => \array_map(fn($item) => \trim($item), \explode(',', $searchable)),
        //     default => $searchable
        // };

        //$this->initComponentAttributeBags();
        $this->attributes = new ComponentAttributeBag();
    }

    public function hasStaticDataSource(): bool
    {
        return $this->dataSrc instanceof StaticDataDataSource;
    }

    public function data()
    {
        return [
            'attributes' => $this->attributes,
            '__dataTable' => $this,
        ];
    }

    public function __get(string $property): mixed
    {
        if ($property === 'name') {
            if ($this->tableAttributes->has('name')) {
                $name = $this->tableAttributes['name'];

                if (!empty(\trim($name))) {
                    return $name;
                }
            }

            return null;
        }

        if ($property === 'id') {
            if ($this->tableAttributes->has(key: 'id')) {
                $id = $this->tableAttributes['id'];

                if (!empty(\trim($id))) {
                    return $id;
                }
            }

            return null;
        }

        $trace = debug_backtrace(limit: 1);

        $ex = new \ErrorException('Undefined property: ' . static::class . "::$property", severity: E_WARNING, filename: $trace[0]['file'], line: $trace[0]['line']);

        try {
            $rp = new \ReflectionProperty(\Exception::class, 'trace');
            $exTrace = $rp->getValue($ex);

            if (!empty($exTrace)) {
                \array_shift($exTrace);
            }

            $rp->setValue($ex, $exTrace);
        } catch (\Throwable $t) {
        }

        throw $ex;
    }


    // /**
    //  *  
    //  * @inheritDoc
    //  * 
    //  * @see \Livewire\Wireable::fromLivewire
    //  * 
    //  * @return self
    //  */
    // public static function fromLivewire($value)
    // {
    //     return \decrypt($value['erickcomp-lw-dt']);

    //     return $this->$debugPayload
    //         ? \unserialize($value['erickcomp-lw-dt'])
    //         : \decrypt($value['erickcomp-lw-dt']);
    // }

    // /**
    //  *  
    //  * @inheritDoc
    //  * 
    //  * @see \Livewire\Wireable::toLivewire
    //  * 
    //  * @return array
    //  */
    // public function toLivewire()
    // {
    //     return ['erickcomp-lw-dt' => \encrypt($this)];
    // }

    public function hasTableActions()
    {
        return $this->isSearchable()
            || $this->isFilterable()
            || $this->hasBulkActions()
            || $this->hasPerPageOptions();
    }

    public function preset(): Preset
    {
        return $this->loadedPreset ??= Preset::loadFromName($this->preset ?? 'empty');
    }

    public function isSearchable(): bool
    {
        return isset($this->search) && $this->search !== null;
    }

    public function isFilterable(): bool
    {
        return $this->initalizedFilters() && count($this->filters->filtersItems) > 0;
    }

    public function hasBulkActions()
    {
        // @TODO: implement bulk actions
        return false;
    }

    public function hasSearchableColumns(): bool
    {
        foreach ($this->columns as $col) {
            if ($col->isSearchable()) {
                return true;
            }
        }

        return false;
    }

    // public function getTrAttributesModifierCode(): string
    // {
    //     return $this->trAttributesModifierCode;
    // }

    public function hasRowLevelConfiguration(): bool
    {
        $rowLevelVars = [
            'rowLevelClassCode',
            'rowLevelClassCodePath',
            'rowLevelStyleCode',
            'rowLevelStyleCodePath',
            'rowLevelAttributesCode',
            'rowLevelAttributesCodePath',
        ];

        foreach ($rowLevelVars as $var) {
            if (isset($this->$var)) {
                return true;
            }
        }

        return false;
    }

    public function getTrAttributesForRow(
        LwDataTable $lwDataTable,
        mixed $row,
        object $loop,
    ): ComponentAttributeBag {

        $trAttributes = $this->tbodyTrAttributes;

        if (isset($this->rowLevelAttributesCodePath)) {
            $trAttributes = $trAttributes->merge($this->evaluateRowModifierUsingContext($this->rowLevelAttributesCodePath, $lwDataTable, $row, $loop));
        }

        if (isset($this->rowLevelClassCodePath)) {
            $trAttributes = $trAttributes->class($this->evaluateRowModifierUsingContext($this->rowLevelClassCodePath, $lwDataTable, $row, $loop));
        }

        if (isset($this->rowLevelStyleCodePath)) {
            $trAttributes = $trAttributes->style($this->evaluateRowModifierUsingContext($this->rowLevelStyleCodePath, $lwDataTable, $row, $loop));
        }

        return $trAttributes;
    }

    protected function evaluateRowModifierUsingContext(string $filepath, LwDataTable $___lwDataTable, mixed $__row, object $loop)
    {
        return include $filepath;
    }

    // public function setTrAttributesModifierCode(string $trAttributesModifierCode)
    // {
    //     $this->trAttributesModifierCode = \trim($trAttributesModifierCode);
    // }

    public function hasCustomRenderedSearch(): bool
    {
        return !empty($this->searchRendererCode);
    }

    public function getCustomSearchRendererCode(): string
    {
        return $this->searchRendererCode;
    }

    public function setCustomSearchRendererCode(string $searchRendererCode, ComponentAttributeBag $attributes)
    {
        $this->searchRendererCode = \trim($searchRendererCode);
        $this->searchRendererCodeAttributes = $attributes;
    }

    public function addDataColumn(ComponentAttributeBag $columnAttributes)
    {
        $this->columns->push(DataColumn::fromComponentAttributeBag($columnAttributes));
    }

    public function addCustomRenderedColumn(ComponentAttributeBag $columnAttributes, ?string $customRendererCode)
    {
        //$this->columns[] = Builders\ColumnFactory::make($this, $columnAttributes);
        $this->columns->push(CustomRenderedColumn::fromComponentAttributeBag($columnAttributes, customRendererCode: $customRendererCode));
    }

    public function hasFooter(): bool
    {
        return isset($this->footer) && $this->footer instanceof Footer;
    }

    public function setFooter(ComponentAttributeBag $filterContainerAttributes, string $rendererCode)
    {
        $this->footer = new Footer($filterContainerAttributes, $rendererCode);
    }

    public function addAction() {}

    public function initFilters(ComponentAttributeBag $filterContainerAttributes)
    {
        $this->filters = new Filters(componentAttributes: $filterContainerAttributes, preset: $this->preset());
    }

    public function initalizedFilters()
    {
        return !empty($this->filters);
    }

    public function addFilter(ComponentAttributeBag $filterAttributes, ?string $customRendererCode = null)
    {
        if (!$this->initalizedFilters()) {
            $this->initFilters(new ComponentAttributeBag());
        }
        $this->filters->filtersItems[] = new Filter($filterAttributes, $customRendererCode);
    }

    // public function loadData()
    // {
    //     $data = $this->executeServerCallable($this->dataProvider)
    //     dd($this->dataProvider);
    // }

    // public function action(string $action, ...$params)
    // {
    //     return $this->callAction($action, $params);
    // }

    public function render(): \Closure
    {
        return $this->doRender(...);
    }

    public function hasPerPageOptions(): bool
    {
        return count($this->perPageOptions) > 1;
    }

    public function perPageOptionsForSelect(): array
    {
        $options = \array_combine($this->perPageOptions, $this->perPageOptions);
        $toRemove = [];

        foreach ($options as $optionVal => $optionLabel) {

            if ($optionVal === static::PER_PAGE_MAX) {
                $toRemove[] = $optionVal;

                $options[$this->maxPerPage] = $this->maxPerPage;

                continue;

            }

            if ($optionVal === static::PER_PAGE_ALL) {
                $toRemove[] = $optionVal;

                $options[static::PER_PAGE_ALL_OPTION_VALUE] = __('erickcomp_lw_data_table::messages.per_page_option_all_label');

                continue;
            }

            $intOptionVal = (int) $optionVal;

            if ($intOptionVal > $this->maxPerPage) {
                $toRemove[] = $optionVal;
            }
        }

        foreach ($toRemove as $key) {
            unset($options[$key]);
        }

        // if (\array_key_exists(static::PER_PAGE_ALL, $options)) {
        //     unset($options[static::PER_PAGE_ALL]);
        //     $options[static::PER_PAGE_ALL_OPTION_VALUE] = __('erickcomp_lw_data_table::messages.per_page_option_all_label');
        // }

        return $options;
    }

    public function isUsingDefaultPaginationViews(): bool
    {
        return $this->paginationView === null;
    }

    public function shouldStylePagination(): ?bool
    {
        return match (static::$useDefaultPaginationStylingForDefaultPaginationViews) {
            true => $this->isUsingDefaultPaginationViews(),
            false => false,
            null => static::class === DataTable::class && $this->isUsingDefaultPaginationViews()
        };
    }

    public function paginationView(): string
    {
        return match ($this->paginationView) {
            null => $this->preset()->get('pagination.view', static::$defaultPaginationView),
            'bootstrap' => 'livewire::bootstrap',
            'tailwind' => 'livewire::tailwind',
            default => null
        };
    }

    public function paginationSimpleView(): string
    {
        return match ($this->paginationView) {
            null => $this->preset()->get('pagination.simple-view', static::$defaultPaginationSimpleView),
            'bootstrap' => 'livewire::simple-bootstrap',
            'tailwind' => 'livewire::simple-tailwind',
            default => null
        };
    }

    public function buildXModelAttribute(string $filterProperty, ?string $range = null): string
    {
        //
        $xModel = "dtData()['$filterProperty']['$this->dataField']['$this->name']";

        if ($range === null) {
            return $xModel;
        }

        if (!\in_array(\strtolower($range), ['from', 'to'])) {
            throw new \LogicException("Invalid range: $range. The valid values for the \$range parameter are: \"from\", \"to\"");
        }

        return "{$xModel}['$range']";
    }

    /**
     * Caches a DataTable instance to a file
     * 
     * @return string Returns the base filename used to create the DataTable object caches
     */
    public static function toCache(DataTable $dataTable): string
    {
        $cacheBaseFilename = $dataTable->cacheBaseFilename();
        $cacheFilePath = \storage_path("framework/views/{$cacheBaseFilename}.php");

        $isUsingStaticDataSource = $dataTable->hasStaticDataSource();
        $dataSrc = $dataTable->dataSrc;

        if ($isUsingStaticDataSource) {
            unset($dataTable->dataSrc);
        }

        static::createCodeCachesFiles($dataTable);

        if (!\file_exists($cacheFilePath)) {
            \file_put_contents(
                $cacheFilePath,
                \serialize($dataTable),
            );
        }

        if ($isUsingStaticDataSource) {
            $dataTable->dataSrc = $dataSrc;
        }

        return $cacheBaseFilename;
    }

    public static function fromCache(string $cacheBaseFilename, ?StaticDataDataSource $staticDataDataSource = null): null|static
    {
        $filePath = \storage_path("framework/views/{$cacheBaseFilename}.php");

        if (!\file_exists($filePath)) {
            return null;
        }

        try {
            $dataTable = \unserialize(\file_get_contents($filePath));

            if (!$dataTable instanceof static) {
                return null;
            }

            if ($staticDataDataSource !== null) {
                $dataTable->dataSrc = $staticDataDataSource;
            }

            return $dataTable;
        } catch (\Throwable $t) {
            $log = 'Could not restore [' . static::class . '] object from file: [' . $filePath . '].' . PHP_EOL
                . PHP_EOL
                . $t::class . ": {$t->getMessage()}" . PHP_EOL
                . 'Stack trace:' . PHP_EOL
                . $t->getTraceAsString();

            Log::notice($log);

            return null;
        }
    }

    protected function cacheBaseFilename(): string
    {
        if (!isset($this->cacheBaseFilename)) {
            $varsToExcludeFromSerializationHash = [
                'rowLevelClassCode',
                'rowLevelClassCodePath',
                'rowLevelStyleCode',
                'rowLevelStyleCodePath',
                'rowLevelAttributesCode',
                'rowLevelAttributesCodePath',
            ];

            foreach ($varsToExcludeFromSerializationHash as $var) {
                if (isset($dataTable->$var)) {
                    $$var = $dataTable->$var;

                    unset($dataTable->$var);
                }
            }

            $serialized = \serialize($this);
            $this->cacheBaseFilename = "x-{$this->componentName}___" . \md5($serialized);

            foreach ($varsToExcludeFromSerializationHash as $var) {
                if (isset($$var)) {
                    $dataTable->$var = $$var;
                }
            }
        }

        return $this->cacheBaseFilename;
    }

    protected static function createCodeCachesFiles(DataTable $dataTable)
    {
        $varsToHandle = [
            'rowLevelClass',
            'rowLevelStyle',
            'rowLevelAttributes',
        ];

        foreach ($varsToHandle as $var) {
            $codeVar = "{$var}Code";

            if (isset($dataTable->$codeVar)) {
                $code = $dataTable->$codeVar;
                unset($dataTable->$codeVar);

                $wrappedCode = <<<PHP
                    <?php
                    return ($code);
                    PHP;

                $pathVar = "{$codeVar}Path";
                $cacheFilePath = \storage_path("framework/views/{$dataTable->cacheBaseFilename()}___$var.php");
                $dataTable->$pathVar = $cacheFilePath;

                if (!file_exists($cacheFilePath)) {
                    \file_put_contents(
                        $cacheFilePath,
                        $wrappedCode,
                    );
                }
            }
        }
    }

    // public function runAction(string $action, ...$params)
    // {
    //     // try {

    //     //     $return = $this->actions->run($action, ...$params);

    //     //     if ($return === false) {
    //     //         return new DataTableActionResponse(isOk: false, message: 'Erro ao executar ');
    //     //     }
    //     // } catch (ErrorMessageForUserException $e1) {
    //     //     return new DataTableActionResponse(isOk: false, message: $e1->getMessage());
    //     // }
    //     // //$this->get
    // }

    // protected function executeServerCallable($callable, ...$params)
    // {
    //     return ImplicitlyBoundMethod::call(app(), $callable, $params);
    // }

    protected function viewFile()
    {
        return \substr(__FILE__, 0, -3) . 'blade.php';
    }

    protected function doRender(array $data)
    {
        $this->fillComponentAttributeBags($data['attributes']);
        return $this->makeViewObject($data);
    }

    protected function extractPublicProperties()
    {
        return [
            ...parent::extractPublicProperties(),
            '__dataTable' => $this,
        ];
    }

    protected function initComponentAttributeBags()
    {
        $this->containerAttributes = new ComponentAttributeBag(/*$this->defaultContainerAttributes*/);
        //$this->tableAttributes = new ComponentAttributeBag($this->defaultTableAttributes);
        //$this->theadAttributes = new ComponentAttributeBag($this->defaultTheadAttributes);
        //$this->theadTrAttributes = new ComponentAttributeBag($this->defaultTheadTrAttributes);
        //$this->theadSearchTrAttributes = new ComponentAttributeBag($this->defaultTheadSearchTrAttributes);
        //$this->theadSearchThAttributes = new ComponentAttributeBag($this->defaultTheadSearchThAttributes);
        //$this->thAttributes = new ComponentAttributeBag($this->defaultThAttributes);
        //$this->tbodyAttributes = new ComponentAttributeBag($this->defaultTbodyAttributes);
        //$this->tbodyTrAttributes = new ComponentAttributeBag($this->defaultTbodyTrAttributes);
        // //$this->tbodyTdAttributes = new ComponentAttributeBag();
        // //$this->tfootAttributes = new ComponentAttributeBag();
        // //$this->tfootTrAttributes = new ComponentAttributeBag();
        // //$this->tfootTdAttributes = new ComponentAttributeBag();
    }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'tableAttributes', //default
            'container-' => 'containerAttributes',
            'thead-tr-' => 'theadTrAttributes',
            'thead-search-tr-' => 'theadSearchTrAttributes',
            'thead-search-th-' => 'theadSearchThAttributes',
            'thead-' => 'theadAttributes',
            'th-' => 'thAttributes',
            'tbody-tr-' => 'tbodyTrAttributes',
            'tbody-' => 'tbodyAttributes',
        ];
    }
    protected function getDefaultPerPageOptions(): array
    {
        // if (\is_a($this->dataSrc, EloquentModel::class, true)) {
        //     $model = new $this->dataSrc;
        // } else {
        //     $model = new class () extends EloquentModel {};
        // }

        // return [$model->getPerPage()];
        return $this->dataSrc instanceof EloquentDataSource || $this->dataSrc instanceof EloquentBuilderDataSource
            ? [$this->dataSrc->modelPerPage()]
            : $this->preset()->get('pagination.default-per-page-for-non-eloquent-data-sources', [static::PER_PAGE_ALL]);
    }
}
