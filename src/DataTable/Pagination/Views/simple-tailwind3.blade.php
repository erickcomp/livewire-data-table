@php
    if (!isset($scrollTo)) {
        $scrollTo = 'body';
    }

    // Scrolling waits for Livewire's response: `$wire.<action>()` returns a promise that resolves only after the new page
    // has been rendered. Scrolling on click, as before, aimed at the old page: when the new page was taller, the browser
    // clamped the scroll to the old page's height and the view stopped midway down the table instead of at its top.
    $onClick = function (string $call) use ($scrollTo): string {
        if ($scrollTo === false) {
            return "\$wire.{$call}";
        }

        return "const target = \$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}'); "
            . "\$wire.{$call}.then(() => target?.scrollIntoView())";
    };
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="{{ __('erickcomp_lw_data_table::messages.pagination.aria_pagination_navigation') }}"
            class="flex justify-between">
            <span>
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span
                        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:text-gray-600 dark:bg-gray-800 dark:border-gray-600">
                        {!! __('erickcomp_lw_data_table::messages.pagination.previous') !!}
                    </span>
                @else
                    @if(method_exists($paginator, 'getCursorName'))
                        <button type="button" dusk="previousPage"
                            wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->previousCursor()->encode() }}"
                            x-on:click="{{ $onClick("setPage('" . $paginator->previousCursor()->encode() . "','" . $paginator->getCursorName() . "')") }}" wire:loading.attr="disabled"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300">
                            {!! __('erickcomp_lw_data_table::messages.pagination.previous') !!}
                        </button>
                    @else
                        <button
                            type="button" x-on:click="{{ $onClick("previousPage('" . $paginator->getPageName() . "')") }}" wire:loading.attr="disabled"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300">
                            {!! __('erickcomp_lw_data_table::messages.pagination.previous') !!}
                        </button>
                    @endif
                @endif
            </span>

            <span>
                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    @if(method_exists($paginator, 'getCursorName'))
                        <button type="button" dusk="nextPage"
                            wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->nextCursor()->encode() }}"
                            x-on:click="{{ $onClick("setPage('" . $paginator->nextCursor()->encode() . "','" . $paginator->getCursorName() . "')") }}" wire:loading.attr="disabled"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300">
                            {!! __('erickcomp_lw_data_table::messages.pagination.next') !!}
                        </button>
                    @else
                        <button type="button" x-on:click="{{ $onClick("nextPage('" . $paginator->getPageName() . "')") }}" wire:loading.attr="disabled"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300">
                            {!! __('erickcomp_lw_data_table::messages.pagination.next') !!}
                        </button>
                    @endif
                @else
                    <span
                        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:text-gray-600 dark:bg-gray-800 dark:border-gray-600">
                        {!! __('erickcomp_lw_data_table::messages.pagination.next') !!}
                    </span>
                @endif
            </span>
        </nav>
    @endif
</div>
