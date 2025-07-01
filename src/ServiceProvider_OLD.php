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
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\ComponentTagCompiler;
use Livewire\Livewire;
use ErickComp\RawBladeComponents\RawComponent;

class ServiceProvider extends LaravelAbstractServiceProvider
{
    public function register() {}

    public function boot()
    {
        // Blade::precompiler(function (string $templateStr) {
        //     $templateStr = \trim($templateStr);

        //     return $templateStr;
        // });

        $this->registerRawBladeComponents();
        $this->registerBladeComponents();
        $this->registerLivewireComponents();

        Paginator::useBootstrap();
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

    protected function registerRawBladeComponents()
    {
        $this->registerColumnTdRawComponent();
        $this->registerTrAttributesRawComponent();
        $this->registerSearchRawComponent();
    }

    protected function registerColumnTdRawComponent()
    {
        $this->registerRawBladeComponent(
            tag: 'x-data-table.column.td',
            openingCode: <<<'COL_TD_COMPILER_CODE'
                    <?php

                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable\Column) {
                        throw new \LogicException("You can only use the [x-data-table.column.td] component as a direct child of the [x-data-table.column] component");
                    }

                    $component->setCustomRendererCode (<<<'___DATATABLE__RENDERER___'
                COL_TD_COMPILER_CODE,
            closingCode: '___DATATABLE__RENDERER___, $__rawComponentAttributes); ?>',
        );
        /*
        Blade::prepareStringsForCompilationUsing(function (string $templateStr) {
            $regexOpening = $this->rawComponentOpeningTagRegex('x-data-table.column.td');
            $regexClosing = $this->rawComponentClosingTagRegex('x-data-table.column.td');

            $code = <<<'COL_TD_COMPILER_CODE'
                    <?php
                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable\Column) {
                        throw new \LogicException("You can only use the [x-data-table.column.td] as a direct child of the [x-data-table.column] component");
                    }

                    $component->setCustomRendererCode (<<<'___DATATABLE__RENDERER___'
                COL_TD_COMPILER_CODE;

            $endCode = '___DATATABLE__RENDERER___);?>';

            return preg_replace([$regexOpening, $regexClosing], [$code, $endCode], $templateStr);
        });
        */
    }

    protected function registerTrAttributesRawComponent()
    {
        $this->registerRawBladeComponent(
            tag: 'x-data-table.tr-attributes',
            openingCode: <<<'COL_TD_COMPILER_CODE'
                    <?php
                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable) {
                        throw new \LogicException("You can only use the [x-data-table.tr-attributes] as a direct child of the [x-data-table] component");
                    }

                    $component->setTrAttributesModifierCode (<<<'___DATATABLE__RENDERER___'
                COL_TD_COMPILER_CODE,
            closingCode: '___DATATABLE__RENDERER___); ?>',
        );

        /*
        Blade::prepareStringsForCompilationUsing(function (string $templateStr) {
            $regexOpening = $this->rawComponentOpeningTagRegex('x-data-table.tr-attributes');
            $regexClosing = $this->rawComponentClosingTagRegex('x-data-table.tr-attributes');

            $code = <<<'COL_TD_COMPILER_CODE'
                    <?php
                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable) {
                        throw new \LogicException("You can only use the [x-data-table.tr-attributes] as a direct child of the [x-data-table] component");
                    }

                    $component->setTrAttributesModifierCode (<<<'___DATATABLE__RENDERER___'
                COL_TD_COMPILER_CODE;

            $endCode = '___DATATABLE__RENDERER___); ?>';

            return preg_replace([$regexOpening, $regexClosing], [$code, $endCode], $templateStr);
        });
        */
    }
    protected function registerSearchRawComponent()
    {
        $this->registerRawBladeComponent(
            tag: 'x-data-table.search',
            openingCode: <<<'COL_TD_COMPILER_CODE'
                    <?php
                    if(!isset($component) || !$component instanceof \ErickComp\LivewireDataTable\DataTable) {
                        throw new \LogicException("You can only use the [x-data-table.search] as a direct child of the [x-data-table] component");
                    }

                    $component->setCustomSearchRendererCode (<<<'___DATATABLE__RENDERER___'
                COL_TD_COMPILER_CODE,
            closingCode: '___DATATABLE__RENDERER___, $__rawComponentAttributes); ?>',
        );
    }

    protected function registerRawBladeComponent(string $tag, string $openingCode, string $closingCode, ?string $selfClosingCode = null)
    {
        RawComponent::rawComponent(
            $tag,
            $openingCode,
            $closingCode,
            $selfClosingCode
        );
        /*
        if (!\str_starts_with($tag, 'x-')) {
            $tag = "x-$tag";
        }

        Blade::prepareStringsForCompilationUsing(function (string $templateStr) use ($tag, $openingCode, $closingCode, $selfClosingCode) {
            $regexOpening = $this->rawComponentOpeningTagRegex($tag);
            $regexClosing = $this->rawComponentClosingTagRegex($tag);
            $regexSelfClosing = $this->rawComponentSelfClosingTagRegex($tag);

            // \preg_match_all($regexSelfClosing, $templateStr, $matches);
            // dd($matches);

            $callbackOpening = function ($match) use ($openingCode) {
                $attributes = $this->getAttributesFromAttributeString($match['attributes']);

                return '<?php ' . PHP_EOL
                    . '$__previousRawComponentAttributes = $__rawComponentAttributes ?? new \\Illuminate\\View\\ComponentAttributeBag([]);' . PHP_EOL
                    . '$__rawComponentAttributes = new \\Illuminate\\View\\ComponentAttributeBag([' . $this->componentAttributesToString($attributes) . ']);' . PHP_EOL
                    . '?>' . PHP_EOL
                    . $openingCode;
            };

            $callbackClosing = function ($match) use ($closingCode) {
                return $closingCode . PHP_EOL
                    . '<?php $__rawComponentAttributes = $__previousRawComponentAttributes; $__previousRawComponentAttributes = null; ?>' . PHP_EOL;
            };

            $callbackSelfClosing = $selfClosingCode === null
                ? function ($match) use ($tag) {
                    return "<?php throw new \LogicException('The component [$tag] is not meant to be used with the self-closing tag syntax'); ?>";
                }
                : function ($match) use ($selfClosingCode) {
                    $attributes = $this->getAttributesFromAttributeString($match['attributes']);

                    return '<?php ' . PHP_EOL
                        . '$__previousRawComponentAttributes = $__rawComponentAttributes ?? new \\Illuminate\\View\\ComponentAttributeBag([]);' . PHP_EOL
                        . '$__rawComponentAttributes = new \\Illuminate\\View\\ComponentAttributeBag([' . $this->componentAttributesToString($attributes) . ']);' . PHP_EOL
                        . $selfClosingCode
                        . '<?php $__rawComponentAttributes = $__previousRawComponentAttributes;' . PHP_EOL
                        . '$__previousRawComponentAttributes = null;' . PHP_EOL
                        . '?>';
                }
            ;


            //$compiled = preg_replace_callback

            return preg_replace_callback_array(
                [$regexSelfClosing => $callbackSelfClosing, $regexOpening => $callbackOpening, $regexClosing => $callbackClosing],
                $templateStr,
            );

            
            // return preg_replace_callback($pattern, function (array $matches) {
            //     $this->boundAttributes = [];
            //
            //     $attributes = $this->getAttributesFromAttributeString($matches['attributes']);
            //
            //     return $this->componentString($matches[1], $attributes);
            // }, $value);
            

            //return preg_replace([$regexOpening, $regexClosing], [$openingCode, $closingCode], $templateStr);
        });
        */
    }

    protected function rawComponentOpeningTagRegex(string $rawComponentName)
    {
        // if (!\str_starts_with($rawComponentName, 'x-')) {
        //     $rawComponentName = 'x-' . $rawComponentName;
        // }

        $quotedComponent = \preg_quote($rawComponentName, '/');
        $pattern = "/
            <
                \s*
                $quotedComponent
                (?<attributes>
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

    protected function rawComponentClosingTagRegex(string $rawComponentName)
    {
        // if (!\str_starts_with($rawComponentName, 'x-')) {
        //     $rawComponentName = 'x-' . $rawComponentName;
        // }

        $quotedComponent = \preg_quote($rawComponentName, '/');

        return "/<\/\s*$quotedComponent\s*>/";
    }

    protected function rawComponentSelfClosingTagRegex(string $rawComponentName)
    {
        if (!\str_starts_with($rawComponentName, 'x-')) {
            $rawComponentName = 'x-' . $rawComponentName;
        }

        $quotedComponent = \preg_quote($rawComponentName, '/');

        return "/
            <
                \s*
                $quotedComponent
                \s*
                (?<attributes>
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
            \/>
        /x";
    }

    protected function getAttributesFromAttributeString(string $attributesString)
    {
        $this->getLaravelComponentTagCompiler()->resetBoundAttributes();
        return $this->getLaravelComponentTagCompiler()->getAttributesFromAttributeString($attributesString);
    }

    protected function componentAttributesToString(array $attributes, bool $escapeBound = true): string
    {
        return $this->getLaravelComponentTagCompiler()->attributesToString($attributes, $escapeBound);
    }

    protected function getLaravelComponentTagCompiler()
    {
        static $compiler = null;

        if ($compiler === null) {
            $compiler = new class (app()->make(ComponentTagCompiler::class)) extends ComponentTagCompiler {

                public function __construct(ComponentTagCompiler $componentTagCompiler)
                {
                    parent::__construct($componentTagCompiler->aliases, $componentTagCompiler->namespaces, $componentTagCompiler->blade);
                }

                public function resetBoundAttributes()
                {
                    $this->boundAttributes = [];
                }

                public function getAttributesFromAttributeString(string $attributesString)
                {
                    return parent::getAttributesFromAttributeString($attributesString);
                }

                public function attributesToString(array $attributes, $escapeBound = true)
                {
                    return parent::attributesToString($attributes, $escapeBound);
                }
            };
        }

        return $compiler;
    }
}
