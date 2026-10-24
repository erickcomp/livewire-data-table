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
        <nav>
            <ul class="pagination justify-content-center">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ __('erickcomp_lw_data_table::messages.pagination.previous') }}</span>
                    </li>
                @else
                    @if(method_exists($paginator, 'getCursorName'))
                        <li class="page-item">
                            <button dusk="previousPage" type="button" class="page-link"
                                wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->previousCursor()->encode() }}"
                                x-on:click="{{ $onClick("setPage('" . $paginator->previousCursor()->encode() . "','" . $paginator->getCursorName() . "')") }}"
                                wire:loading.attr="disabled">{{ __('erickcomp_lw_data_table::messages.pagination.previous') }}</button>
                        </li>
                    @else
                        <li class="page-item">
                            <button type="button"
                                dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                                class="page-link" x-on:click="{{ $onClick("previousPage('" . $paginator->getPageName() . "')") }}"
                                wire:loading.attr="disabled">{{ __('erickcomp_lw_data_table::messages.pagination.previous') }}</button>
                        </li>
                    @endif
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    @if(method_exists($paginator, 'getCursorName'))
                        <li class="page-item">
                            <button dusk="nextPage" type="button" class="page-link"
                                wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->nextCursor()->encode() }}"
                                x-on:click="{{ $onClick("setPage('" . $paginator->nextCursor()->encode() . "','" . $paginator->getCursorName() . "')") }}"
                                wire:loading.attr="disabled">{{ __('erickcomp_lw_data_table::messages.pagination.next') }}</button>
                        </li>
                    @else
                        <li class="page-item">
                            <button type="button"
                                dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                                class="page-link" x-on:click="{{ $onClick("nextPage('" . $paginator->getPageName() . "')") }}"
                                wire:loading.attr="disabled">{{ __('erickcomp_lw_data_table::messages.pagination.next') }}</button>
                        </li>
                    @endif
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ __('erickcomp_lw_data_table::messages.pagination.next') }}</span>
                    </li>
                @endif
            </ul>
        </nav>
    @endif
</div>
