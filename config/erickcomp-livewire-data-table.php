<?php

use Illuminate\Support\Facades\View;

return [
    'columns-search-debounce-ms' => 250,
    'query-string-search' => 'search',
    'query-string-filters' => 'filters',
    'query-string-param-cols-search' => 'cols-search',
    'presets' => [
        'empty' => [
            'extends' => null,
            'columns-search-debounce-ms' => 250,
            'query-string-search' => 'search',
            'query-string-filters' => 'filters',
            'query-string-param-cols-search' => 'cols-search',
        ],
        'vanilla' => [
            'extends' => 'empty', // ?string

            'main-container' => [
                'class' => ['lw-dt-container'],
            ],
            'actions' => [
                'container' => [
                    'class' => ['lw-dt-actions-container'],
                ],
                'row' => [
                    'class' => ['lw-dt-actions-row'],
                ],
            ],

            'search' => [
                'container' => [
                    'class' => ['lw-dt-search-container'],
                ],
                'input' => [
                    'class' => ['lw-dt-search-input'],
                ],
                'button' => [
                    'class' => ['lw-dt-search-button'],
                    'icon' => <<<'HTML'
                        <svg xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 512 512"
                            width="14" height="14"
                            fill="currentColor"
                            style="vertical-align: middle;">
                            <path
                                d="M495 466.1l-110.1-110.1c31.1-37.7 48-84.6 48-134 0-56.4-21.9-109.3-61.8-149.2-39.8-39.9-92.8-61.8-149.1-61.8-56.3 0-109.3 21.9-149.2 61.8C33.1 112.7 11.2 165.7 11.2 222c0 56.3 21.9 109.3 61.8 149.2 39.8 39.8 92.8 61.8 149.2 61.8 49.5 0 96.4-16.9 134-48l110.1 110c8 8 20.9 8 28.9 0 8-8 8-20.9 0-28.9zM101.7 342.2c-32.2-32.1-49.9-74.8-49.9-120.2 0-45.4 17.7-88.2 49.8-120.3 32.1-32.1 74.8-49.8 120.3-49.8 45.4 0 88.2 17.7 120.3 49.8 32.1 32.1 49.8 74.8 49.8 120.3 0 45.4-17.7 88.2-49.8 120.3-32.1 32.1-74.9 49.8-120.3 49.8-45.4 0-88.1-17.7-120.2-49.9z" />
                        </svg>
                    HTML,
                ],
            ],

            'filters' => [
                'collapsible' => true,
                'container' => [
                    'class' => [],
                ],
                'title' => [
                    'class' => ['lw-dt-filters-title'],
                ],
                'toggle-button' => [
                    'class' => ['lw-dt-filters-toggle-button'],
                    //'use-default-icon' => true,
                    'icon' => <<<'ICON'
                        <svg xmlns="http://www.w3.org/2000/svg"
                            width="14" height="14" viewBox="0 0 24 24"
                            style="vertical-align: middle;">
                            <path d="M3 4h18l-7 10v5l-4 1v-6z" fill="currentColor" />
                        </svg>
                    ICON,
                ],
                'apply-button' => [
                    'container' => [
                        'class' => ['lw-dt-filter-apply-container'],
                    ],
                    'class' => ['filters-apply-button'],
                    //'icon' => ''
                ],
                'item' => [
                    'class' => ['lw-dt-filter-item'],
                    'content' => [
                        'class' => ['lw-dt-filter-item-content'],
                        'legend' => [
                            'class' => ['lw-dt-filter-item-content-legend'],
                            'span' => [
                                'class' => ['lw-dt-filter-item-content-legend-span'],
                            ],
                        ],
                        'range' => [
                            'class' => ['lw-dt-filter-item-content-filter-range'],
                            'label' => [
                                'from' => [
                                    'class' => ['lw-dt-filter-item-content-filter-range-label-from'],
                                ],
                                'to' => [
                                    'class' => ['lw-dt-filter-item-content-filter-range-label-to'],
                                ],
                            ],
                            'input' => [
                                'from' => [
                                    'class' => ['lw-dt-filter-item-content-filter-range-input-from'],
                                ],
                                'to' => [
                                    'class' => ['lw-dt-filter-item-content-filter-range-input-to'],
                                ],
                            ],
                        ],
                        'input-text' => [
                            'class' => ['lw-dt-filter-item-content-input-text'],
                        ],
                        'input-date' => [
                            'class' => ['lw-dt-filter-item-content-input-date'],
                        ],
                        'input-datetime-local' => [
                            'class' => ['lw-dt-filter-item-content-input-datetime-local'],
                        ],
                        'input-number' => [
                            'class' => ['lw-dt-filter-item-content-input-number'],
                        ],
                        'select' => [
                            'class' => ['lw-dt-filter-item-content-select'],
                        ],
                    ],
                ],
            ],

            'applied-filters' => [
                'container' => [
                    'class' => ['lw-dt-applied-filters-container'],
                ],
                'label' => [
                    'class' => ['lw-dt-active-filters-label'],
                ],
                'applied-filter-item' => [
                    'class' => ['applied-filter-item'],
                ],
                'button-remove-applied-filter-item' => [
                    'class' => ['applied-filter-remove-button'],
                    'content' => 'x',
                ],
            ],

            'table' => [
                'class' => ['lw-dt-table'],
                'thead' => [
                    'class' => ['lw-dt-thead'],
                    'tr' => [
                        'class' => ['lw-dt-thead-tr'],
                        'th' => [
                            'class' => ['lw-dt-thead-tr-th'],
                            'sorting' => [
                                'show-indicators' => true,
                                'indicator-class' => ['lw-dt-sort'],
                                'indicator-asc-class' => ['lw-dt-sort', 'lw-dt-sort-asc'],
                                'indicator-desc-class' => ['lw-dt-sort', 'lw-dt-sort-desc'],
                                'indicator-none-class' => ['lw-dt-sort', 'lw-dt-sort-none'],
                            ],
                        ],
                        'search' => [
                            'debounce-ms' => 250,
                            'class' => ['lw-dt-columns-search'],
                            'th' => [
                                'class' => ['lw-dt-columns-search-th'],
                                'input' => [
                                    'class' => ['lw-dt-columns-search-input'],
                                ],
                            ],
                        ],
                    ],
                ],
                'tbody' => [
                    'class' => ['lw-dt-tbody'],
                    'tr' => [
                        'class' => ['lw-dt-tbody-tr'],
                        'td' => [
                            'class' => ['lw-dt-tbody-tr-td'],
                        ],
                        'nodatafound' => [
                            'class' => ['lw-dt-nodatafound-td'],
                        ],
                    ],
                ],
                'tfoot' => [
                    'class' => ['lw-dt-tfoot'],
                    'tr' => [
                        'class' => ['lw-dt-tfoot-tr'],

                        'th' => [
                            'class' => ['lw-dt-tfoot-tr-th'],
                        ],
                        'td' => [
                            'class' => ['lw-dt-tfoot-tr-td'],
                        ],
                    ],
                ],
            ],

            'pagination' => [
                'view' => 'livewire::bootstrap',
                'simple-view' => 'livewire::simple-bootstrap',
                //'default-style-for-pagination' => true,
            ],

            'assets' => [<<<'CSS'
                <style>
                    .lw-dt table.lw-dt-table {
                        margin-top: 1rem;
                    }
                    .lw-dt table.lw-dt-table .lw-dt-nodatafound-td {
                        text-align: center;
                        vertical-align: middle;
                        padding: 1rem;
                    }

                    .lw-dt th .lw-dt-sort {
                        cursor: pointer;
                    }

                    .lw-dt:not(.without-sorting-indicators) .lw-dt-sort {
                        display: inline-block;
                        width: 12px;
                        height: 12px;
                        background-size: contain;
                        background-repeat: no-repeat;
                        background-position: center right;
                        background-size: 12px 12px;
                        margin-left: 10px;
                    }

                    .lw-dt:not(.without-sorting-indicators) .lw-dt-sort.lw-dt-sort-none {
                        background-image: url("data:image/svg+xml,%3Csvg width='14' height='14' viewBox='0 0 14 14' fill='none' xmlns='http://www.w3.org/2000/svg' class='p-icon p-datatable-sort-icon' aria-hidden='true' sortOrder='0' data-pc-section='sorticon'%3E%3Cpath d='M5.64515 3.61291C5.47353 3.61291 5.30192 3.54968 5.16644 3.4142L3.38708 1.63484L1.60773 3.4142C1.34579 3.67613 0.912244 3.67613 0.650309 3.4142C0.388374 3.15226 0.388374 2.71871 0.650309 2.45678L2.90837 0.198712C3.17031 -0.0632236 3.60386 -0.0632236 3.86579 0.198712L6.12386 2.45678C6.38579 2.71871 6.38579 3.15226 6.12386 3.4142C5.98837 3.54968 5.81676 3.61291 5.64515 3.61291Z' fill='currentColor'%3E%3C/path%3E%3Cpath d='M3.38714 14C3.01681 14 2.70972 13.6929 2.70972 13.3226V0.677419C2.70972 0.307097 3.01681 0 3.38714 0C3.75746 0 4.06456 0.307097 4.06456 0.677419V13.3226C4.06456 13.6929 3.75746 14 3.38714 14Z' fill='currentColor'%3E%3C/path%3E%3Cpath d='M10.6129 14C10.4413 14 10.2697 13.9368 10.1342 13.8013L7.87611 11.5432C7.61418 11.2813 7.61418 10.8477 7.87611 10.5858C8.13805 10.3239 8.5716 10.3239 8.83353 10.5858L10.6129 12.3652L12.3922 10.5858C12.6542 10.3239 13.0877 10.3239 13.3497 10.5858C13.6116 10.8477 13.6116 11.2813 13.3497 11.5432L11.0916 13.8013C10.9561 13.9368 10.7845 14 10.6129 14Z' fill='currentColor'%3E%3C/path%3E%3Cpath d='M10.6129 14C10.2426 14 9.93552 13.6929 9.93552 13.3226V0.677419C9.93552 0.307097 10.2426 0 10.6129 0C10.9833 0 11.2904 0.307097 11.2904 0.677419V13.3226C11.2904 13.6929 10.9832 14 10.6129 14Z' fill='currentColor'%3E%3C/path%3E%3C/svg%3E");
                    }

                    .lw-dt:not(.without-sorting-indicators) .lw-dt-sort.lw-dt-sort-asc {
                        background-image: url("data:image/svg+xml,%3Csvg width='14' height='14' viewBox='0 0 14 14' fill='none' xmlns='http://www.w3.org/2000/svg' class='p-icon p-datatable-sort-icon' aria-hidden='true' sorted='true' sortOrder='1' data-pc-section='sorticon'%3E%3Cpath d='M3.63435 0.19871C3.57113 0.135484 3.49887 0.0903226 3.41758 0.0541935C3.255 -0.0180645 3.06532 -0.0180645 2.90274 0.0541935C2.82145 0.0903226 2.74919 0.135484 2.68597 0.19871L0.427901 2.45677C0.165965 2.71871 0.165965 3.15226 0.427901 3.41419C0.689836 3.67613 1.12338 3.67613 1.38532 3.41419L2.48726 2.31226V13.3226C2.48726 13.6929 2.79435 14 3.16467 14C3.535 14 3.84209 13.6929 3.84209 13.3226V2.31226L4.94403 3.41419C5.07951 3.54968 5.25113 3.6129 5.42274 3.6129C5.59435 3.6129 5.76597 3.54968 5.90145 3.41419C6.16338 3.15226 6.16338 2.71871 5.90145 2.45677L3.64338 0.19871H3.63435ZM13.7685 13.3226C13.7685 12.9523 13.4615 12.6452 13.0911 12.6452H7.22016C6.84984 12.6452 6.54274 12.9523 6.54274 13.3226C6.54274 13.6929 6.84984 14 7.22016 14H13.0911C13.4615 14 13.7685 13.6929 13.7685 13.3226ZM7.22016 8.58064C6.84984 8.58064 6.54274 8.27355 6.54274 7.90323C6.54274 7.5329 6.84984 7.22581 7.22016 7.22581H9.47823C9.84855 7.22581 10.1556 7.5329 10.1556 7.90323C10.1556 8.27355 9.84855 8.58064 9.47823 8.58064H7.22016ZM7.22016 5.87097H7.67177C8.0421 5.87097 8.34919 5.56387 8.34919 5.19355C8.34919 4.82323 8.0421 4.51613 7.67177 4.51613H7.22016C6.84984 4.51613 6.54274 4.82323 6.54274 5.19355C6.54274 5.56387 6.84984 5.87097 7.22016 5.87097ZM11.2847 11.2903H7.22016C6.84984 11.2903 6.54274 10.9832 6.54274 10.6129C6.54274 10.2426 6.84984 9.93548 7.22016 9.93548H11.2847C11.655 9.93548 11.9621 10.2426 11.9621 10.6129C11.9621 10.9832 11.655 11.2903 11.2847 11.2903Z' fill='currentColor'%3E%3C/path%3E%3C/svg%3E");
                    }

                    .lw-dt:not(.without-sorting-indicators) .lw-dt-sort.lw-dt-sort-desc {
                        background-image: url("data:image/svg+xml,%3Csvg width='14' height='14' viewBox='0 0 14 14' fill='none' xmlns='http://www.w3.org/2000/svg' class='p-icon p-datatable-sort-icon' aria-hidden='true' sorted='true' sortOrder='-1' data-pc-section='sorticon'%3E%3Cpath d='M4.93953 10.5858L3.83759 11.6877V0.677419C3.83759 0.307097 3.53049 0 3.16017 0C2.78985 0 2.48275 0.307097 2.48275 0.677419V11.6877L1.38082 10.5858C1.11888 10.3239 0.685331 10.3239 0.423396 10.5858C0.16146 10.8477 0.16146 11.2813 0.423396 11.5432L2.68146 13.8013C2.74469 13.8645 2.81694 13.9097 2.89823 13.9458C2.97952 13.9819 3.06985 14 3.16017 14C3.25049 14 3.33178 13.9819 3.42211 13.9458C3.5034 13.9097 3.57565 13.8645 3.63888 13.8013L5.89694 11.5432C6.15888 11.2813 6.15888 10.8477 5.89694 10.5858C5.63501 10.3239 5.20146 10.3239 4.93953 10.5858ZM13.0957 0H7.22468C6.85436 0 6.54726 0.307097 6.54726 0.677419C6.54726 1.04774 6.85436 1.35484 7.22468 1.35484H13.0957C13.466 1.35484 13.7731 1.04774 13.7731 0.677419C13.7731 0.307097 13.466 0 13.0957 0ZM7.22468 5.41935H9.48275C9.85307 5.41935 10.1602 5.72645 10.1602 6.09677C10.1602 6.4671 9.85307 6.77419 9.48275 6.77419H7.22468C6.85436 6.77419 6.54726 6.4671 6.54726 6.09677C6.54726 5.72645 6.85436 5.41935 7.22468 5.41935ZM7.6763 8.12903H7.22468C6.85436 8.12903 6.54726 8.43613 6.54726 8.80645C6.54726 9.17677 6.85436 9.48387 7.22468 9.48387H7.6763C8.04662 9.48387 8.35372 9.17677 8.35372 8.80645C8.35372 8.43613 8.04662 8.12903 7.6763 8.12903ZM7.22468 2.70968H11.2892C11.6595 2.70968 11.9666 3.01677 11.9666 3.3871C11.9666 3.75742 11.6595 4.06452 11.2892 4.06452H7.22468C6.85436 4.06452 6.54726 3.75742 6.54726 3.3871C6.54726 3.01677 6.85436 2.70968 7.22468 2.70968Z' fill='currentColor'%3E%3C/path%3E%3C/svg%3E");
                    }

                    .lw-dt .lw-dt-pagination-container .pagination {
                        display: flex;
                        list-style: none;
                        padding: 0;
                        margin: 0;
                    }

                    .lw-dt .lw-dt-pagination-container .pagination li {
                        display: inline-block;
                        padding: 5px 10px;
                        text-decoration: none;
                    }

                    .lw-dt .lw-dt-actions-row {
                        display: flex;
                        align-items: flex-end;
                        gap: 10px;
                    }

                    .lw-dt .lw-dt-actions-row button.lw-dt-filters-toggle-button {
                        cursor: pointer;
                    }

                    .lw-dt .lw-dt-actions-row button.lw-dt-filters-toggle-button.active {
                        border-bottom: none;
                        z-index: 2;
                        padding-bottom: calc(1.0rem + 4px);
                        margin-bottom: calc(-1.0rem - 1px);
                        border-color: #ccc;
                        border-width: 0.15em;
                        border-top-left-radius: 4px;
                        border-top-right-radius: 4px;
                        border-bottom-left-radius: 0;
                        border-bottom-right-radius: 0;
                        border-style: solid;
                        border-bottom-width: 0;
                        border-bottom-color: white;
                        border-bottom-style: none;
                        background-color: white;
                    }

                    .lw-dt .lw-dt-actions-row .lw-dt-filters-container {
                        width: 80%;
                        min-width: 1024px;
                        border: 1px solid #ccc;
                        margin-top: 1rem;
                        padding: 10px;
                        display: flex;
                        position: relative;
                        z-index: 1;
                        background: white;
                        align-items: stretch;
                        flex-wrap: wrap;
                    }

                    .lw-dt .lw-dt-actions-row .lw-dt-filters-container .lw-dt-filters-title {
                        font-size: 1.3rem;
                        font-weight: bold;
                        flex-basis: 100%;
                        padding: 0.25rem;
                    }

                    .lw-dt .lw-dt-actions-row .lw-dt-filters-container .lw-dt-filter-apply-container {
                        flex-basis: 100%;
                        padding-top: 1rem;
                        padding-left: 0.25rem;
                    }

                    .lw-dt .lw-dt-actions-row .lw-dt-applied-filters-container {
                        padding-top: 0.75rem;
                    }

                    .lw-dt .lw-dt-actions-row .lw-dt-applied-filters-container .lw-dt-active-filters-label {
                        padding: 0.25rem;
                    }

                    .lw-dt .lw-dt-actions-row .lw-dt-applied-filters-container .applied-filter-item {
                        padding: 0.25rem;
                        font-weight: bold;
                    }

                    @media (max-width: 1024px) {
                        .lw-dt .lw-dt-actions-row .lw-dt-filters-container {
                            width: 100%;
                            min-width: 90%;
                        }
                    }

                    @media (max-width: 640px) {
                        .lw-dt .lw-dt-actions-row .lw-dt-filters-container {
                            align-items: center;
                        }
                    }

                    .lw-dt .lw-dt-actions-row .lw-dt-filters-container .lw-dt-filter-item {
                        padding: 0.25rem;
                        min-width: 240px;
                        border-radius: 6px;
                    }

                    @media (max-width: 1279px) {
                        .lw-dt .lw-dt-actions-row .lw-dt-filters-container .lw-dt-filter-item {
                            flex: 0 0 content;
                        }
                    }

                    @media (max-width: 551px) {
                        .lw-dt .lw-dt-actions-row .lw-dt-filters-container .lw-dt-filter-item {
                            min-width: 100%;
                        }
                    }

                    .lw-dt .lw-dt-actions-row .lw-dt-filters-container .lw-dt-filter-item .lw-dt-filter-item-content {
                        padding: 0.5em 1em;
                        border: 1px solid #ccc;
                        border-radius: 0.25em;
                        background-color: #f9f9f9;
                        min-height: 8rem;
                    }

                    .lw-dt .lw-dt-actions-row .lw-dt-filters-container .lw-dt-filter-item .lw-dt-filter-item-content legend {
                        font-weight: bold;
                    }

                    .lw-dt .lw-dt-actions-row .lw-dt-filters-container .lw-dt-filter-item .lw-dt-filter-item-content.lw-dt-filter-item-content-filter-range {
                        display: flex;
                        flex-direction: column;
                        gap: 0.25rem;
                    }

                    .lw-dt-actions-row {
                        display: flex;
                        align-items: flex-end;
                        gap: 10px;
                    }
                </style>
                CSS,
            ],

            'scripts' => [],
        ],
    ],
];
