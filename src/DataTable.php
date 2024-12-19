<?php

namespace ErickComp\LivewireDataTable;

use Illuminate\View\ComponentAttributeBag;
use Illuminate\Support\Str;
use Illuminate\View\Component as BladeComponent;
use Livewire\Wireable;

class DataTable extends BladeComponent implements Wireable
{
    public string $dataSrcId = 'id';

    public function __construct(
        public $dataSrc,
        public array $columns = [],
        public array $filters = [],
        public string|false $search = false,
        public array $columnsSearch = [],
        public array $actions = [],
        public bool $searchable = false,
    ) {}
    
    /**
     *  
     * @inheritDoc
     * 
     * @see \Livewire\Wireable::fromLivewire
     */
    public static function fromLivewire($value) {}
    {
        return \decrypt($value);
    }

    /**
     *  
     * @inheritDoc
     * 
     * @see \Livewire\Wireable::toLivewire
     */
    public function toLivewire() {
        return \encrypt($this);
    }

    public function addColumn(ComponentAttributeBag $attributes, bool $isActionsColumn, bool $isSearchable, bool $isFilterable, bool $isUnique)
    {
        //$column = new Builders\Column($attributes, $isSearchable, $isFilterable, $isAction);
        //$column->isActionsColumn = $isActionsColumn;
        //$column->isSearchable = $isSearchable;
        //$column->isFilterable = $isFilterable;
        //$column->attributes = $attributes;

        $this->columns[] = new Builders\Column($this, $attributes, $isSearchable, $isFilterable, $isAction);
    }

    public function addAction() {}

    public function addFilter() {}

    public function loadData() {}

    public function action(string $action, ...$params)
    {
        return $this->callAction($action, $params);
    }

    public function render(): \Closure
    {
        //return view()->file(Str::replaceEnd('.php', '.blade.php', __FILE__));
        return $this->doRender(...);
    }

    protected function doRender(array $data): string
    {
        return view()
            ->file(\substr(__FILE__, 0, -3) . 'blade.php')
            ->with($data)
            ->render();
    }

    protected function extractPublicProperties()
    {
        return [
            ...parent::extractPublicProperties(),
            '__dataTable' => $this,
        ];
    }

}
