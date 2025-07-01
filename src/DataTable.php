<?php

namespace ErickComp\LivewireDataTable;

use ErickComp\LivewireDataTable\Builders\Column\BaseColumn;
use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use ErickComp\LivewireDataTable\DataTable\BaseDataTableComponent;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Filters;
use ErickComp\LivewireDataTable\Src\Drawer\DataTableActionResponse;
use ErickComp\LivewireDataTable\Src\Drawer\ErrorMessageForUserException;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\Component as BladeComponent;
use Illuminate\View\ComponentAttributeBag;
use Livewire\ImplicitlyBoundMethod;
use Livewire\Wireable;

class DataTable extends BaseDataTableComponent implements Wireable
{
    use FillsComponentAttributeBags;

    public static string $defaultPaginationView = 'livewire::bootstrap';
    public static string $defaultPaginationSimpleView = 'livewire::simple-bootstrap';
    public static ?bool $useDefaultPaginationStylingForDefaultPaginationViews = true;
    protected array $defaultContainerAttributes = ['class' => 'lw-dt-container'];
    protected array $defaultTableAttributes = [];
    protected array $defaultTheadAttributes = [];
    protected array $defaultTheadTrAttributes = [];
    protected array $defaultTheadSearchTrAttributes = [];
    protected array $defaultTheadSearchThAttributes = [];
    protected array $defaultThAttributes = [];
    protected array $defaultTbodyAttributes = [];
    protected array $defaultTbodyTrAttributes = [];
    protected string $trAttributesModifierCode = '';
    protected string $searchRendererCode;
    protected ComponentAttributeBag $searchRendererCodeAttributes;
    public string $dataIdentityColumn = 'id';
    public string $sortingClassPrefix = 'lw-dt-sort';
    public ?string $paginationView = null;
    public ?string $paginationCode = null;
    public ?string $lengthAwarePaginationView = null;
    public ?string $simplePaginationView = null;
    public array $perPageOptions = [];
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

    public ?string $name {
        get {
            if ($this->tableAttributes->has('name')) {
                $name = $this->tableAttributes['name'];

                if (!empty(\trim($name))) {
                    return $name;
                }
            }

            return null;
        }
    }

    public ?string $id {
        get {
            if ($this->tableAttributes->has(key: 'id')) {
                $id = $this->tableAttributes['id'];

                if (!empty(\trim($id))) {
                    return $id;
                }
            }

            return null;
        }
    }

    /** @var string[] $assets */
    public array $assets = [];

    /** @var string[] $scripts */
    public array $scripts = [];

    public array|true $searchable {
        get {
            if (\is_array($this->searchable)) {
                return $this->searchable;
            }

            return \array_map(fn(BaseColumn $col) => $col->name, $this->columns);
        }
    }

    public function __construct(
        public ?string $dataProvider = null,
        public ?string $dataProviderGetDataMethod = 'dataTable',

        public bool $withoutSortingIndicators = false,

        /** @var BaseColumn[] */
        public array $columns = [],
        //public array $filters = [],
        public string|false $search = false,
        public array $columnsSearch = [],
        public array $actions = [],
        public string $pageName = 'page',
        public bool $filtersToggleNoDefaultIcon = false,
        ?string $paginationView = null,
        string|array $perPageOptions = [],
        string|array|bool $searchable = false,

    ) {
        $this->dataProvider = $dataProvider;
        $this->dataProviderGetDataMethod = $dataProviderGetDataMethod;
        $this->paginationView = $paginationView;

        if (\is_string($perPageOptions)) {
            $perPageOptions = \array_filter(\array_map(trim(...), \explode(',', $perPageOptions)));
        }

        if (empty($perPageOptions)) {
            $perPageOptions = $this->getDefaultPerPageOptions();
        }

        $this->perPageOptions = $perPageOptions;

        $this->searchable = match (getype($searchable)) {
            'true' => [],
            'string' => \array_map(fn($item) => \trim($item), \explode(',', $searchable)),
            'array' => $searchable
        };

        $this->initComponentAttributeBags();
    }

    /**
     *  
     * @inheritDoc
     * 
     * @see \Livewire\Wireable::fromLivewire
     * 
     * @return self
     */
    public static function fromLivewire($value)
    {
        return \decrypt($value['erickcomp-lw-dt']);
    }

    /**
     *  
     * @inheritDoc
     * 
     * @see \Livewire\Wireable::toLivewire
     * 
     * @return array
     */
    public function toLivewire()
    {
        return ['erickcomp-lw-dt' => \encrypt($this)];
    }

    public function hasTableActions()
    {
        return $this->isSearchable()
            || $this->isFilterable()
            || $this->hasBulkActions()
            || $this->hasPerPageOptions();
    }

    public function isSearchable()
    {
        return $this->searchable;
    }

    public function isFilterable()
    {
        // @TODO: implement filters
        //return false;

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

    public function getTrAttributesModifierCode(): string
    {
        return $this->trAttributesModifierCode;
    }

    public function setTrAttributesModifierCode(string $trAttributesModifierCode)
    {
        $this->trAttributesModifierCode = \trim($trAttributesModifierCode);
    }

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

    //public function addColumn(ComponentAttributeBag $columnAttributes)
    public function addColumn(Column $columnComponent)
    {
        //$this->columns[] = Builders\ColumnFactory::make($this, $columnAttributes);
        $this->columns[] = Builders\ColumnFactory::make($columnComponent);
    }

    public function addAction() {}

    public function initFilters(ComponentAttributeBag $filterContainerAttributes)
    {
        $this->filters = new Filters(componentAttributes: $filterContainerAttributes);

        // dd(
        //     $this->filters->rowLength,
        //     $this->filters->title,
        //     $this->filters->collapsible,
        //     $this->filters->containerAttributes,
        //     $this->filters->filterRowAttributes,
        //     $this->filters->filterItemsAttributes,
        // );
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
        // $toLw = $this->toLivewire();
        // $fromLw = static::fromLivewire($toLw);

        // dd($this, $toLw, $fromLw);

        //return view()->file(Str::replaceEnd('.php', '.blade.php', __FILE__));
        return $this->doRender(...);
    }

    public function hasPerPageOptions(): bool
    {
        return count($this->perPageOptions) > 1;
    }

    public function isUsingDefaultPaginationViews(): bool
    {
        return $this->paginationView === null;
    }

    public function paginationView(): string
    {
        return match ($this->paginationView) {
            null => static::$defaultPaginationView,
            'bootstrap' => 'livewire::bootstrap',
            'tailwind' => 'livewire::tailwind',
            default => $this->paginationView
        };
    }

    public function paginationSimpleView(): string
    {
        return match ($this->paginationView) {
            null => static::$defaultPaginationSimpleView,
            'bootstrap' => 'livewire::simple-bootstrap',
            'tailwind' => 'livewire::simple-tailwind',
            default => $this->paginationView
        };
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
        $this->containerAttributes = new ComponentAttributeBag($this->defaultContainerAttributes);
        $this->tableAttributes = new ComponentAttributeBag($this->defaultTableAttributes);
        $this->theadAttributes = new ComponentAttributeBag($this->defaultTheadAttributes);
        $this->theadTrAttributes = new ComponentAttributeBag($this->defaultTheadTrAttributes);
        $this->theadSearchTrAttributes = new ComponentAttributeBag($this->defaultTheadSearchTrAttributes);
        $this->theadSearchThAttributes = new ComponentAttributeBag($this->defaultTheadSearchThAttributes);
        $this->thAttributes = new ComponentAttributeBag($this->defaultThAttributes);
        $this->tbodyAttributes = new ComponentAttributeBag($this->defaultTbodyAttributes);
        $this->tbodyTrAttributes = new ComponentAttributeBag($this->defaultTbodyTrAttributes);
        //$this->tbodyTdAttributes = new ComponentAttributeBag();
        //$this->tfootAttributes = new ComponentAttributeBag();
        //$this->tfootTrAttributes = new ComponentAttributeBag();
        //$this->tfootTdAttributes = new ComponentAttributeBag();
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
        if (\is_a($this->dataProvider, EloquentModel::class, true)) {
            $model = new $this->dataProvider;
        } else {
            $model = new class () extends EloquentModel {};
        }

        return [$model->getPerPage()];
    }
}
