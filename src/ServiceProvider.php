<?php

namespace ErickComp\LivewireDataTable;

use ErickComp\LivewireDataTable\DataTable\Action;
use \Illuminate\Support\ServiceProvider as LaravelAbstractServiceProvider;
use Illuminate\Support\Facades\Blade;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Livewire\Livewire;
use ErickComp\LivewireDataTable\DataTable\Col;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Filters;
use ErickComp\LivewireDataTable\DataTable\BulkActions;
use ErickComp\LivewireDataTable\DataTable\BulkAction;

class ServiceProvider extends LaravelAbstractServiceProvider
{
    public function register() {}

    public function boot()
    {
        $this->registerBladeDirectives();
        $this->registerBladeComponents();
        $this->registerLivewireComponents();
    }

    protected function registerBladeComponents()
    {
        Blade::component(DataTable::class, 'data-table');
        Blade::component(Col::class, 'data-table.col');
        Blade::component(Action::class, 'data-table.action');
        Blade::component(Filters::class, 'data-table.filters');
        Blade::component(Filter::class, 'data-table.filter');
        Blade::component(BulkActions::class, 'data-table.bulk-actions');
        Blade::component(BulkAction::class, 'data-table.bulk-action');
    }

    protected function registerLivewireComponents()
    {
        Livewire::component('lw-data-table', LwDataTable::class);
    }

    protected function registerBladeDirectives()
    {
        $this->registerDataTableColCustom();
    }

    protected function registerDataTableColCustom()
    {
        $directiveName = config('erickcomp-livewire-datatable.col-custom', ['dataTableColCustom', 'endDataTableColCustom']);

        Blade::directive($directiveName[0], function (string $expression) {
            return '<?php $component->setCustomRendererCode (<<<\'___DATATABLE__RENDERER___\'';
        });

        Blade::directive($directiveName[1], function (string $expression) {
            return "___DATATABLE__RENDERER___);?>";
        });
    }
}
