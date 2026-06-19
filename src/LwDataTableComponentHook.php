<?php

namespace ErickComp\LivewireDataTable;

use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Livewire\ComponentHook as LivewireComponentHook;

use function Livewire\before;

class LwDataTableComponentHook extends LivewireComponentHook
{
    public static function provide()
    {
        before('mount', function ($component, $params, $id, $parent, $htmlAttributes = []) {

            if (\array_key_exists('data-table', $htmlAttributes)) {
                $params['dataTable'] ??= $htmlAttributes['data-table'];
                unset($htmlAttributes['data-table']);
            }

            if (!$component instanceof LwDataTable || !isset($params['dataTable'])) {
                return;
            }

            \Livewire\invade($component)->mountDataTable($params['dataTable']);
        });

        before('hydrate', function ($component, $memo, $context) {

            if (!$component instanceof LwDataTable) {
                return;
            }

            return \Livewire\invade($component)->hydrateDataTable();
        });
    }
}
