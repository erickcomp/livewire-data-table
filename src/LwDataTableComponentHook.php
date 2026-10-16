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

            if (!$component instanceof LwDataTable) {
                return;
            }

            // The DataTable arrives under a different key depending on the Livewire version that compiled the
            // <livewire:lw-data-table :data-table="..."> tag in DataTable.blade.php and on the one running it:
            // Livewire 3 compiles it to a "dataTable" param, while Livewire 4 compiles it to "data-table" and,
            // as no property or mount() parameter matches it, hands it over as an HTML attribute. A view compiled
            // by one major version stays in the view cache after switching to the other, so all forms are accepted.
            $dataTable = $params['dataTable']
                ?? $params['data-table']
                ?? $htmlAttributes['data-table']
                ?? $htmlAttributes['dataTable']
                ?? null;

            if ($dataTable === null) {
                return;
            }

            \Livewire\invade($component)->mountDataTable($dataTable);
        });

        before('hydrate', function ($component, $memo, $context) {

            if (!$component instanceof LwDataTable) {
                return;
            }

            return \Livewire\invade($component)->hydrateDataTable();
        });
    }
}
