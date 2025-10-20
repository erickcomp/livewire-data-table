<?php

namespace ErickComp\LivewireDataTable;

use \Illuminate\Support\ServiceProvider as LaravelAbstractServiceProvider;
use ErickComp\LivewireDataTable\DataTable\Action;
use ErickComp\LivewireDataTable\DataTable\BulkAction;
use ErickComp\LivewireDataTable\DataTable\BulkActions;
use ErickComp\LivewireDataTable\DataTable\Column;
use ErickComp\LivewireDataTable\DataTable\Filter;
use ErickComp\LivewireDataTable\DataTable\Filters;
use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use ErickComp\RawBladeComponents\RawComponent;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Illuminate\Support\Str;

class ServiceProvider extends LaravelAbstractServiceProvider
{
    public function register()
    {
        $this->setupConfig();
    }

    public function boot()
    {
        $this->registerLwDataTableComponentHook();
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'erickcomp_lw_data_table');
        $this->registerEarlyBladeDirectives();
        $this->registerRawBladeComponents();
        $this->registerBladeComponents();
        $this->registerLivewireComponents();

        //Paginator::useBootstrap();
    }

    protected function registerLwDataTableComponentHook()
    {
        $this->app['livewire']->componentHook($this->app->make(LwDataTableComponentHook::class));
    }

    protected function setupConfig()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/erickcomp-livewire-data-table.php',
            'erickcomp-livewire-data-table',
        );
    }

    protected function registerBladeComponents()
    {
        Blade::component(DataTable::class, 'data-table');
    }

    protected function registerLivewireComponents()
    {
        Livewire::component('lw-data-table', LwDataTable::class);
    }

    protected function registerEarlyBladeDirectives()
    {
        $codePropSetter = function (string $componentCodeProp, string $expression) {

            $compiled = Str::kebab($componentCodeProp) . "=\"$expression\"";
            return $compiled;

            //$code = '<?php' . PHP_EOL . 'return ' . $expression . ';' . PHP_EOL;
            /*
            $code = <<<PHP_CODE
                    <?php
                    return $expression;

                PHP_CODE;

            $escapedCode = <<<PHP_CODE
                    <?php \$component->$componentCodeProp = <<<'___LW_DT_PHP_CODE___'
                    $code
                    ___LW_DT_PHP_CODE___;
                    ?>
                PHP_CODE;

            return $escapedCode;
            //$componentParsedProp = Str::kebab($componentCodeProp) . '="' . $code . '"';

            //return $componentParsedProp;
            */
        };

        $componentPattern = "/
            <
                \s*
                x[-\:]([\w\-\:\.]*)
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?=.*@(rowClass|rowAttributes|rowStyle))
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:rowClass)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:rowStyle)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:rowAttributes)(\( (?: (?>[^()]+) | (?-1) )* \))
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


        $directivePattern = '/
            (?<!@)
            @(rowClass|rowStyle|rowAttributes)
            \(
                (
                    (?>
                        [^()]+
                        |
                        \( (?2) \)
                    )*
                )
            \)
        /xms';

        $rowDirectivesCompiler = function (string $templateStr) use ($componentPattern, $directivePattern, $codePropSetter) {
            $templateStrWithoutComments = \preg_replace('/{{--(.*?)--}}/s', '', $templateStr);
            $templateStrWithCompiledDirectives = preg_replace_callback(
                $componentPattern,
                function ($matches) use ($directivePattern, $codePropSetter) {
                    $tag = $matches[0];

                    return preg_replace_callback(
                        $directivePattern,

                        function ($m) use ($codePropSetter) {
                            [$full, $directive, $innerExpression] = $m;
                            $innerExpression = \htmlentities($innerExpression);

                            $directiveWithoutAtTr = Str::replaceStart('row', '', $directive);
                            $replacedDirective = 'row-level-' . Str::kebab($directiveWithoutAtTr) . "-code=\"$innerExpression\"";

                            return $replacedDirective;
                        },
                        $tag,
                    );
                },
                $templateStrWithoutComments,
            );

            return $templateStrWithCompiledDirectives;
        };

        Blade::prepareStringsForCompilationUsing($rowDirectivesCompiler);
    }

    protected function olodRegisterEarlyBladeDirectives()
    {
        $codePropSetter = function (string $componentCodeProp, string $expression) {
            //$code = '<?php' . PHP_EOL . 'return ' . $expression . ';' . PHP_EOL;
            $code = <<<PHP_CODE
                    <?php
                    return $expression;

                PHP_CODE;

            $escapedCode = <<<PHP_CODE
                    <?php \$component->$componentCodeProp = <<<'___LW_DT_PHP_CODE___'
                    $code
                    ___LW_DT_PHP_CODE___;
                    ?>
                PHP_CODE;

            return $escapedCode;
            //$componentParsedProp = Str::kebab($componentCodeProp) . '="' . $code . '"';

            //return $componentParsedProp;
        };

        $componentPattern = '/
            <
                \s*
                x[-\:]([\w\-\:\.]+)
                (?<attributes>
                    (?:
                        (?!
                            \/?>
                        )
                        .
                    )*?
                    @(?:trClass|trAttributes|trStyle)
                    (?:
                        (?!\/?>)
                        .
                    )*
                )
                \s*
                (?:\/>|>)
            /xsi';


        $directivePattern = '/
            (?<!@)
            @(trClass|trAttributes|trStyle)
            \(
                (
                    (?>
                        [^()]+
                        |
                        \( (?2) \)
                    )*
                )
            \)
        /xms';



        /*
        $earlyDirectivesFinders = [
            '/@trAttributes\((( (?>[^()]+) | (?2) )*)\)/x', fn($match) => $codePropSetter('rowLevelAttributesCode', $match[1] ?? '')],
            '/@trClass\((( (?>[^()]+) | (?2) )*)\)/x', fn($match) => $codePropSetter('rowLevelClassCode', $match[1] ?? '')],
            '/@trStyle\((( (?>[^()]+) | (?2) )*)\)/x', fn($match) => $codePropSetter('rowLevelStyleCode', $match[1] ?? '')]],
        ];
        */

        $directiveRegexes = [
            '@trAttributes' => [
                'pattern' => '/@trAttributes\((( (?>[^()]+) | (?2) )*)\)/x',
                'callback' => fn($match) => $codePropSetter('rowLevelAttributesCode', $match[1] ?? '')
            ],
            '@trClass' => [
                'pattern' => '/@trClass\((( (?>[^()]+) | (?2) )*)\)/x',
                'callback' => fn($match) => $codePropSetter('rowLevelClassCode', $match[1] ?? '')
            ],
            '@trStyle' => [
                'pattern' => '/@trStyle\((( (?>[^()]+) | (?2) )*)\)/x',
                'callback' => fn($match) => $codePropSetter('rowLevelStyleCode', $match[1] ?? '')
            ],
        ];

        $preg_replace_directives = function ($directive, $componentTagMatch) use ($directiveRegexes) {
            $componentTag = $componentTagMatch[0];
            $replaced = preg_replace_callback($directiveRegexes[$directive]['pattern'], $directiveRegexes[$directive]['callback'], $componentTag);

            return $replaced;
        };

        $componentsTagsPatterns = [
            '/<x-[\w\.-:]+[^>]*@trAttributes([^>]*)\/?>/s' => fn($componentTag) => $preg_replace_directives('@trAttributes', $componentTag),
            '/<x-[\w\.-:]+[^>]*@trClass([^>])*\/?>/s' => fn($componentTag) => $preg_replace_directives('@trClass', $componentTag),
            '/<x-[\w\.-:]+[^>]*@trStyle([^>])*\/?>/s' => fn($componentTag) => $preg_replace_directives('@trStyle', $componentTag),
        ];

        Blade::prepareStringsForCompilationUsing(
            function (string $templateStr) use ($componentsTagsPatterns) {
                $templateStr = \preg_replace_callback_array($componentsTagsPatterns, $templateStr);

                return $templateStr;
            }
        );


        //$templateWithParsedDirectives = preg_replace_callback_array()

        /*
        $earlyDirectivesPatternReplacers = [
            '/@trAttributes\((.*?)\)/s' => fn($match) => $codePropSetter('rowLevelAttributesCode', $match[1] ?? ''),
            '/@trClass\((.*?)\)/s' => fn($match) => $codePropSetter('rowLevelClassCode', $match[1] ?? ''),
            '/@trStyle\((.*?)\)/s' => fn($match) => $codePropSetter('rowLevelStyleCode', $match[1] ?? ''),
        ];
        */

        //Blade::prepareStringsForCompilationUsing(
        //    function (string $templateStr) use ($earlyDirectivesPatternReplacers) {
        //        foreach ($earlyDirectivesPatternReplacers as $pattern => $callback) {
        //            $templateStr = \preg_replace_callback($pattern, $callback, $templateStr);
        //        }
        //
        //        return $templateStr;
        //    }
        //);
    }

    protected function registerRawBladeComponents()
    {
        //$this->registerColumnTdRawComponent();
        $this->registerColumnRawComponent();
        $this->registerTrAttributesRawComponent();
        $this->registerSearchRawComponent();
        $this->registerFiltersRawComponent();
        $this->registerFilterRawComponent();
        $this->registerAssetRawComponent();
        $this->registerFooterRawComponent();
    }

    protected function registerColumnRawComponent()
    {
        $this->registerRawBladeComponent(
            tag: 'x-data-table.column',
            openingCode: <<<'COL_COMPILER_CODE'
            <?php

            if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable || $__parentRawComponentTag !== null) {
                throw new \LogicException("You can only use the [x-data-table.column] component as a direct child of the [x-data-table] component");
            }

            $component->addCustomRenderedColumn(
                $__rawComponentAttributes,
                <<<'___DATATABLE__RENDERER___'
            COL_COMPILER_CODE,
            closingCode: <<<'COL_COMPILER_CODE'
            ___DATATABLE__RENDERER___
                    );
                ?>
            COL_COMPILER_CODE,
            selfClosingCode: <<<'COL_COMPILER_CODE'
            <?php
            if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable || $__parentRawComponentTag !== null) {
                throw new \LogicException("You can only use the [x-data-table.column] component as a direct child of the [x-data-table] component");
            }

            $component->addDataColumn($__rawComponentAttributes);
            ?>
            COL_COMPILER_CODE,
        );
    }

    protected function registerTrAttributesRawComponent()
    {
        $this->registerRawBladeComponent(
            tag: 'x-data-table.tr-before-render',
            openingCode: <<<'COL_TD_COMPILER_CODE'
                    <?php
                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable || $__parentRawComponentTag !== null) {
                        throw new \LogicException("You can only use the [x-data-table.tr-before-render] as a direct child of the [x-data-table] component");
                    }

                    $component->setTrAttributesModifierCode (<<<'___DATATABLE__RENDERER___'

                COL_TD_COMPILER_CODE,
            closingCode: PHP_EOL . '___DATATABLE__RENDERER___); ?>',
        );
    }

    protected function registerSearchRawComponent()
    {
        $searchCode = <<<'SEARCH_CODE'
            <?php
            if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable || $__parentRawComponentTag !== null) {
                throw new \LogicException("You can only use the [x-data-table.search] as a direct child of the [x-data-table] component");
            }
            $component->search = new \ErickComp\LivewireDataTable\DataTable\Search($__rawComponentAttributes);
            SEARCH_CODE;

        $this->registerRawBladeComponent(
            tag: 'x-data-table.search',
            openingCode: <<<SEARCH_CODE
                    $searchCode
                    \$component->search->customRendererCode = <<<'___DATATABLE__RENDERER___'
                SEARCH_CODE,
            closingCode: <<<'SEARCH_CODE'
                ___DATATABLE__RENDERER___;
                ?>
                SEARCH_CODE,
            selfClosingCode: <<<SEARCH_CODE
                $searchCode
                ?>
                SEARCH_CODE,
        );
    }

    protected function registerFiltersRawComponent()
    {
        $defaultAttributes = [
            'row-length' => 4,
            'collapsible' => true,
        ];

        $this->registerRawBladeComponent(
            tag: 'x-data-table.filters',
            defaultAttributes: ['row-length' => 4, 'collapsible' => 'true'],
            openingCode: <<<'COL_TD_COMPILER_CODE'
                    <?php
                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable || $__parentRawComponentTag !== null) {
                        throw new \LogicException("You can only use the [x-data-table.filters] as a direct child of the [x-data-table] component");
                    }

                    if ($component->initalizedFilters()) {
                        throw new \LogicException("You can only have one [x-data-table.filters] component per [x-data-table] component");
                    }

                    $component->initFilters($__rawComponentAttributes);

                    $__isBuildingDataTableFilters = true;

                    ?>
                COL_TD_COMPILER_CODE,
            closingCode: '<?php unset($__isBuildingDataTableFilters); ?>',
        );
    }

    protected function registerFilterRawComponent()
    {
        $this->registerRawBladeComponent(
            tag: 'x-data-table.filter',
            openingCode: <<<'OPENING_CODE'
                    <?php

                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable || $__parentRawComponentTag !== 'x-data-table.filters') {
                        throw new \LogicException("You can only use the [x-data-table.filter] as a direct child of the [x-data-table.filters] component");
                    }

                    //if(!isset($__isBuildingDataTableFilters) || $__isBuildingDataTableFilters !== true) {
                    //    throw new \LogicException("You can only use the [x-data-table.filter] as a direct child of the [x-data-table.filters] component");
                    //}

                    $component->addFilter($__rawComponentAttributes, <<<'___DATATABLE__RENDERER___'
                OPENING_CODE,
            closingCode: <<<'CLOSING_CODE'
            ___DATATABLE__RENDERER___);
            ?>
            CLOSING_CODE,
            selfClosingCode: <<<'SELF_CLOSING_CODE'
                    <?php
                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable || $__parentRawComponentTag !== 'x-data-table.filters') {
                        throw new \LogicException("You can only use the [x-data-table.filter] as a direct child of the [x-data-table.filters] component");
                    }

                    $component->addFilter($__rawComponentAttributes);

                    ?>
                SELF_CLOSING_CODE,
        );
    }

    protected function registerAssetRawComponent()
    {
        $this->registerRawBladeComponent(
            tag: 'x-data-table.assets',
            openingCode: <<<'OPENING_CODE'
                    <?php

                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable || $__parentRawComponentTag !== null) {
                        throw new \LogicException("You can only use the [x-data-table.asset] as a direct child of the [x-data-table] component");
                    }

                    $component->assets[] = <<<'___DATATABLE__RENDERER___'
                OPENING_CODE,
            closingCode: <<<'CLOSING_CODE'
            ___DATATABLE__RENDERER___;
            ?>
            CLOSING_CODE,
        );
    }

    protected function registerFooterRawComponent()
    {
        $this->registerRawBladeComponent(
            tag: 'x-data-table.footer',
            openingCode: <<<'FOOTER_COMPILER_CODE'
            <?php

            if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable || $__parentRawComponentTag !== null) {
                throw new \LogicException("You can only use the [x-data-table.footer] component as a direct child of the [x-data-table] component");
            }

            $component->setFooter(
                $__rawComponentAttributes,
            <<<'___DATATABLE__RENDERER___'
            FOOTER_COMPILER_CODE,
            closingCode: <<<'FOOTER_COMPILER_CODE'
            ___DATATABLE__RENDERER___
            );
            ?>
            FOOTER_COMPILER_CODE,
        );
    }

    protected function registerRawBladeComponent(string $tag, string $openingCode, string $closingCode, ?string $selfClosingCode = null, array $defaultAttributes = [])
    {
        RawComponent::rawComponent(
            $tag,
            $openingCode,
            $closingCode,
            $selfClosingCode,
            $defaultAttributes,
        );
    }
}
