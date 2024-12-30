<?php

namespace ErickComp\LivewireDataTable;

use ErickComp\LivewireDataTable\DataTable\Action;
use \Illuminate\Support\ServiceProvider as LaravelAbstractServiceProvider;
use Illuminate\Support\Facades\Blade;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Livewire\Livewire;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Filters;
use ErickComp\LivewireDataTable\DataTable\BulkActions;
use ErickComp\LivewireDataTable\DataTable\BulkAction;
use Illuminate\Support\Str;

class ServiceProvider extends LaravelAbstractServiceProvider
{
    public function register() {}

    public function boot()
    {
        // Blade::precompiler(function (string $templateStr) {
        //     $templateStr = \trim($templateStr);

        //     return $templateStr;
        // });

        $this->registerColumnTdComponentTagsCompiler();
        $this->registerTrAttributesTagsCompiler();
        //$this->registerBladeDirectives();
        $this->registerBladeComponents();
        $this->registerLivewireComponents();
    }

    protected function registerBladeComponents()
    {
        Blade::component(DataTable::class, 'data-table');
        Blade::component(Column::class, 'data-table.column');
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

    // protected function registerBladeDirectives()
    // {
    //     $this->registerDataTableColCustom();
    // }

    /*
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
    */

    protected function registerColumnTdComponentTagsCompiler()
    {
        Blade::prepareStringsForCompilationUsing(function (string $templateStr) {
            //$regexOpening = '/<\s*x-data-table\.column\.td\s*>/';
            $regexOpening = $this->pseudoComponentOpeningTag('x-data-table.column.td');
            $regexClosing = $this->pseudoComponentClosingTag('x-data-table.column.td');

            $code = <<<'COL_TD_COMPILER_CODE'
                    <?php
                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable\Column) {
                        throw new \LogicException("You can only use the [x-data-table.column.td] as a direct child of the [x-data-table.column] component");
                    }

                    $component->setCustomRendererCode (<<<'___DATATABLE__RENDERER___'
                COL_TD_COMPILER_CODE;

            $endCode = '___DATATABLE__RENDERER___);?>';

            //$templateStr = \preg_replace($regexOpening, $code, $templateStr);
            //$templateStr = \preg_replace($regexClosing, $endCode, $templateStr);
            //
            //return $templateStr;

            return preg_replace([$regexOpening, $regexClosing], [$code, $endCode], $templateStr);
        });
    }

    protected function registerTrAttributesTagsCompiler()
    {
        Blade::prepareStringsForCompilationUsing(function (string $templateStr) {
            //$regexOpening = '/<\s*x-data-table\.column\.td\s*>/';
            $regexOpening = $this->pseudoComponentOpeningTag('x-data-table.tr-attributes');
            $regexClosing = $this->pseudoComponentClosingTag('x-data-table.tr-attributes');

            $code = <<<'COL_TD_COMPILER_CODE'
                    <?php
                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable) {
                        throw new \LogicException("You can only use the [x-data-table.tr-attributes] as a direct child of the [x-data-table] component");
                    }

                    $component->setTrAttributesModifierCode (<<<'___DATATABLE__RENDERER___'
                COL_TD_COMPILER_CODE;

            $endCode = '___DATATABLE__RENDERER___); ?>';



            //$templateStr = \preg_replace($regexOpening, $code, $templateStr);
            //$templateStr = \preg_replace($regexClosing, $endCode, $templateStr);
            //
            //return $templateStr;

            return preg_replace([$regexOpening, $regexClosing], [$code, $endCode], $templateStr);
        });
    }

    protected function pseudoComponentOpeningTag(string $pseudoComponentName)
    {
        if (!\str_starts_with($pseudoComponentName, 'x-')) {
            $pseudoComponentName = 'x-' . $pseudoComponentName;
        }

        $quotedComponent = \preg_quote($pseudoComponentName, '/');
        $pattern = "/
            <
                \s*
                $quotedComponent
                (?:
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                (\:\\\$)(\w+)
                            )
                            |
                            (?:
                                [\w\-:.@%]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";

        return $pattern;
    }

    protected function pseudoComponentClosingTag(string $pseudoComponentName)
    {
        if (!\str_starts_with($pseudoComponentName, 'x-')) {
            $pseudoComponentName = 'x-' . $pseudoComponentName;
        }

        $quotedComponent = \preg_quote($pseudoComponentName, '/');

        return "/<\/\s*$quotedComponent\s*>/";
    }
}
