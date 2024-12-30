<?php

namespace ErickComp\LivewireDataTable;

use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use ErickComp\LivewireDataTable\Src\Drawer\DataTableActionResponse;
use ErickComp\LivewireDataTable\Src\Drawer\ErrorMessageForUserException;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\Support\Str;
use Illuminate\View\Component as BladeComponent;
use Livewire\Wireable;
use Livewire\ImplicitlyBoundMethod;
use ErickComp\LivewireDataTable\DataTable\BaseDataTableComponent;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\Builders\Column\BaseColumn;

class DataTable extends BaseDataTableComponent implements Wireable
{
    use FillsComponentAttributeBags;

    public string $dataSrcId = 'id';

    public string $sortingClassPrefix = 'lw-dt-sort';

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

    protected array $defaultContainerAttributes = ['class' => 'lw-dt-container'];
    protected array $defaultTableAttributes = [];
    protected array $defaultTheadAttributes = [];
    protected array $defaultTheadTrAttributes = [];
    protected array $defaultTheadSearchTrAttributes = [];
    protected array $defaultTheadSearchThAttributes = [];
    protected array $defaultThAttributes = [];
    protected array $defaultTbodyAttributes = [];
    protected array $defaultTbodyTrAttributes = [];

    protected string $trAttributesModifierCode;

    public function __construct(
        public ?string $dataSrc = null,

        public bool $withoutDefaultStyles = false,
        public bool $withSortingIndicators = false,

        /** @var BaseColumn[] */
        public array $columns = [],
        public array $filters = [],
        public string|false $search = false,
        public array $columnsSearch = [],
        public array $actions = [],
        public bool $searchable = false,
    ) {
        if ($withoutDefaultStyles === false) {
            $this->withSortingIndicators = true;
        }

        $this->dataSrc = $dataSrc;

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
        $this->trAttributesModifierCode = $trAttributesModifierCode;
    }

    //public function addColumn(ComponentAttributeBag $columnAttributes)
    public function addColumn(Column $columnComponent)
    {
        //$this->columns[] = Builders\ColumnFactory::make($this, $columnAttributes);
        $this->columns[] = Builders\ColumnFactory::make($columnComponent);
    }

    public function addAction() {}

    public function addFilter() {}

    // public function loadData()
    // {
    //     $data = $this->executeServerCallable($this->dataSrc)
    //     dd($this->dataSrc);
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
}
