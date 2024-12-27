<?php

namespace ErickComp\LivewireDataTable;

use ErickComp\LivewireDataTable\Src\Drawer\DataTableActionResponse;
use ErickComp\LivewireDataTable\Src\Drawer\ErrorMessageForUserException;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\Support\Str;
use Illuminate\View\Component as BladeComponent;
use Livewire\Wireable;
use Livewire\ImplicitlyBoundMethod;
use ErickComp\LivewireDataTable\DataTable\BaseDataTableComponent;
use ErickComp\LivewireDataTable\DataTable\Col;
use ErickComp\LivewireDataTable\Builders\Column\BaseColumn;

class DataTable extends BaseDataTableComponent implements Wireable
{
    public string $dataSrcId = 'id';

    public ComponentAttributeBag $tableAttributes;
    public ComponentAttributeBag $theadAttributes;
    public ComponentAttributeBag $theadTrAttributes;
    public ComponentAttributeBag $theadSearchTrAttributes;
    public ComponentAttributeBag $theadSearchTdAttributes;
    public ComponentAttributeBag $thAttributes;
    public ComponentAttributeBag $tbodyAttributes;
    public ComponentAttributeBag $tbodyTrAttributes;
    //public ComponentAttributeBag $tbodyTdAttributes;
    //public ComponentAttributeBag $tfootAttributes;
    //public ComponentAttributeBag $tfootTrAttributes;
    //public ComponentAttributeBag $tfootTdAttributes;

    public iterable $rows = [];

    public function __construct(
        public ?string $dataSrc = null,

        /** @var BaseColumn[] */
        public array $columns = [],
        public array $filters = [],
        public string|false $search = false,
        public array $columnsSearch = [],
        public array $actions = [],
        public bool $searchable = false,
    ) {
        $this->dataSrc = $dataSrc;

        $this->initComponentAttributeBags();
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

    //public function addColumn(ComponentAttributeBag $columnAttributes)
    public function addColumn(Col $columnComponent)
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
        $this->tableAttributes = new ComponentAttributeBag();
        $this->theadAttributes = new ComponentAttributeBag();
        $this->theadTrAttributes = new ComponentAttributeBag();
        $this->theadSearchTrAttributes = new ComponentAttributeBag();
        $this->theadSearchTdAttributes = new ComponentAttributeBag();
        $this->thAttributes = new ComponentAttributeBag();
        $this->tbodyAttributes = new ComponentAttributeBag();
        $this->tbodyTrAttributes = new ComponentAttributeBag();
        //$this->tbodyTdAttributes = new ComponentAttributeBag();
        //$this->tfootAttributes = new ComponentAttributeBag();
        //$this->tfootTrAttributes = new ComponentAttributeBag();
        //$this->tfootTdAttributes = new ComponentAttributeBag();
    }

    protected function fillComponentAttributeBags(ComponentAttributeBag $attributes)
    {
        $tableAttributes = [];
        $theadAttributes = [];
        $theadTrAttributes = [];
        $theadSearchTrAttributes = [];
        $theadSearchTdAttributes = [];
        $thAttributes = [];
        $tbodyAttributes = [];
        $tbodyTrAttributes = [];
        //$tbodyTdAttributes = [];
        //$tfootAttributes = [];
        //$tfootTrAttributes = [];
        //$tfootTdAttributes = [];

        foreach ($attributes->all() as $attrName => $attrVal) {

            if (\str_starts_with($attrName, 'thead-')) {
                $theadAttributes[$attrName] = $attrVal;
            } elseif (\str_starts_with($attrName, 'thead-tr-')) {
                $theadTrAttributes[$attrName] = $attrVal;
            } elseif (\str_starts_with($attrName, 'thead-search-tr-')) {
                $theadSearchTrAttributes[$attrName] = $attrVal;
            } elseif (\str_starts_with($attrName, 'thead-search-td-')) {
                $theadSearchTdAttributes[$attrName] = $attrVal;
            } elseif (\str_starts_with($attrName, 'th-')) {
                $thAttributes[$attrName] = $attrVal;
            } elseif (\str_starts_with($attrName, 'tbody-')) {
                $tbodyAttributes[$attrName] = $attrVal;
            } elseif (\str_starts_with($attrName, 'tbody-tr-')) {
                $tbodyTrAttributes[$attrName] = $attrVal;
                // } elseif (\str_starts_with($attrName, 'tfoot-')) {
                //     $theadAttributes[$attrName] = $attrVal;
                // } elseif (\str_starts_with($attrName, 'tfoot')) {
                //     $theadAttributes[$attrName] = $attrVal;
                // } elseif (\str_starts_with($attrName, 'thead-')) {
                //     $theadAttributes[$attrName] = $attrVal;
                // } elseif (\str_starts_with($attrName, 'thead-')) {
                //     $theadAttributes[$attrName] = $attrVal;
            } else {
                $tableAttributes[$attrName] = $attrVal;
            }
        }

        $this->tableAttributes->setAttributes($tableAttributes);
        $this->theadAttributes->setAttributes($theadAttributes);
        $this->theadTrAttributes->setAttributes($theadTrAttributes);
        $this->theadSearchTrAttributes->setAttributes($theadSearchTrAttributes);
        $this->theadSearchTdAttributes->setAttributes($theadSearchTdAttributes);
        $this->thAttributes->setAttributes($thAttributes);
        $this->tbodyAttributes->setAttributes($tbodyAttributes);
        $this->tbodyTrAttributes->setAttributes($tbodyTrAttributes);
    }
}
