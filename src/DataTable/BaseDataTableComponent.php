<?php

namespace ErickComp\LivewireDataTable\DataTable;

use Illuminate\View\Component as BladeComponent;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\View\View;

abstract class BaseDataTableComponent extends BladeComponent
{
    protected array $viewData = [];

    public function render()
    {
        return $this->makeViewObject();
    }

    protected function viewFile()
    {
        return \substr((new \ReflectionClass(static::class))->getFileName(), 0, -3) . 'blade.php';
    }

    protected function makeViewObject(array $mergeData = []): View
    {
        return ViewFactory::file($this->viewFile(), $this->viewData, $mergeData);
    }
}
