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
        <nav class="d-flex align-items-center justify-content-between">
            <div class="d-flex justify-content-between flex-fill d-sm-none">
                <ul class="pagination">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link">{{ __('erickcomp_lw_data_table::messages.pagination.previous') }}</span>
                        </li>
                    @else
                        <li class="page-item">
                            <button type="button"
                                dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                                class="page-link" x-on:click="{{ $onClick("previousPage('" . $paginator->getPageName() . "')") }}"
                                wire:loading.attr="disabled">{{ __('erickcomp_lw_data_table::messages.pagination.previous') }}</button>
                        </li>
                    @endif

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <button type="button"
                                dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                                class="page-link" x-on:click="{{ $onClick("nextPage('" . $paginator->getPageName() . "')") }}"
                                wire:loading.attr="disabled">{{ __('erickcomp_lw_data_table::messages.pagination.next') }}</button>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link" aria-hidden="true">{{ __('erickcomp_lw_data_table::messages.pagination.next') }}</span>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between">
                <div>
                    <p class="small text-body-secondary">
                        {!! __('erickcomp_lw_data_table::messages.pagination.showing') !!}
                        {!! __('erickcomp_lw_data_table::messages.pagination.showing_from') !!}
                        <span class="fw-bold">{{ $paginator->firstItem() }}</span>
                        {!! __('erickcomp_lw_data_table::messages.pagination.showing_to') !!}
                        <span class="fw-bold">{{ $paginator->lastItem() }}</span>
                        {!! __('erickcomp_lw_data_table::messages.pagination.showing_of') !!}
                        <span class="fw-bold">{{ $paginator->total() }}</span>
                        {!! __('erickcomp_lw_data_table::messages.pagination.showing_results') !!}
                    </p>
                </div>

                <div>
                    <ul class="pagination">
                        {{-- Previous Page Link --}}
                        @if ($paginator->onFirstPage())
                            <li class="page-item disabled" aria-disabled="true"
                                aria-label="{{ __('erickcomp_lw_data_table::messages.pagination.previous') }}">
                                <span class="page-link" aria-hidden="true">&lsaquo;</span>
                            </li>
                        @else
                            <li class="page-item">
                                <button type="button"
                                    dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                                    class="page-link" x-on:click="{{ $onClick("previousPage('" . $paginator->getPageName() . "')") }}" wire:loading.attr="disabled"
                                    aria-label="{{ __('erickcomp_lw_data_table::messages.pagination.previous') }}">&lsaquo;</button>
                            </li>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span>
                                </li>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $paginator->currentPage())
                                        <li class="page-item active" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"
                                            aria-current="page"><span class="page-link">{{ $page }}</span></li>
                                    @else
                                        <li class="page-item" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"><button
                                                type="button" class="page-link"
                                                x-on:click="{{ $onClick("gotoPage(" . $page . ", '" . $paginator->getPageName() . "')") }}">{{ $page }}</button></li>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($paginator->hasMorePages())
                            <li class="page-item">
                                <button type="button"
                                    dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                                    class="page-link" x-on:click="{{ $onClick("nextPage('" . $paginator->getPageName() . "')") }}" wire:loading.attr="disabled"
                                    aria-label="{{ __('erickcomp_lw_data_table::messages.pagination.next') }}">&rsaquo;</button>
                            </li>
                        @else
                            <li class="page-item disabled" aria-disabled="true"
                                aria-label="{{ __('erickcomp_lw_data_table::messages.pagination.next') }}">
                                <span class="page-link" aria-hidden="true">&rsaquo;</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>
    @endif
</div>
