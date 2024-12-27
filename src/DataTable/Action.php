<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\DataTable\BaseDataTableComponent;

class Action extends BaseDataTableComponent
{
    protected function extractPublicProperties()
    {
        return [
            ...parent::extractPublicProperties(),
            '__dataTableColumnAction' => $this,
        ];
    }
}
