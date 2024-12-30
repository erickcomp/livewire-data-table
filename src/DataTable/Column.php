<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\DataTable\BaseDataTableComponent;
use ErickComp\LivewireDataTable\Builders\ActionFactory;
use Illuminate\View\ComponentAttributeBag;
use ErickComp\LivewireDataTable\Builders\Action\BaseAction;

class Column extends BaseDataTableComponent
{
    /**  @var BaseAction[] */
    protected array $actions;
    protected string $customRendererCode;
    protected string $class;

    protected function extractPublicProperties()
    {
        return [
            ...parent::extractPublicProperties(),
            '__dataTableColumn' => $this,
        ];
    }

    public function addAction(Action $actionComponent)
    {
        if (isset($this->customRendererCode)) {
            throw new \LogicException('Cannot add a new action to a column that\'s using custom renderer code');
        }

        if (isset($this->class)) {
            throw new \LogicException('Cannot add a new action to a column that\'s that\'s rendered by a custom class');
        }

        $this->actions[] = ActionFactory::make($actionComponent);
    }

    /**
     * @return BaseAction[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function getCustomRendererCode(): string
    {
        return $this->customRendererCode;
    }

    public function setCustomRendererCode(string $rendererCode)
    {
        $this->customRendererCode = $rendererCode;
    }

    public function isActionsColumn()
    {
        return !empty($this->actions);
    }

    public function isCustomClassColumn()
    {
        return !empty($this->class);
    }

    public function isCustomRenderedCodeColumn()
    {
        return !empty($this->customRendererCode);
    }

    public function isDataColumn()
    {
        return !$this->isActionsColumn() && !$this->isCustomClassColumn() && !$this->isCustomRenderedCodeColumn();
    }
}
