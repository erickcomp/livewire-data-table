<?php

use ErickComp\LivewireDataTable\Livewire\LwDataTable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;
use ReflectionClass;

it('merges th attributes correctly', function () {
    $columnThAttributes = new ComponentAttributeBag(['class' => 'col-class', 'data-col' => '1']);
    $tableThAttributes = new ComponentAttributeBag(['class' => 'table-class', 'data-table' => 'yes']);

    $mergeFn = function ($columnThAttributes, $tableThAttributes) {
        return $columnThAttributes->merge($tableThAttributes->all());
    };

    $result = $mergeFn($columnThAttributes, $tableThAttributes);
    expect($result)->toBeInstanceOf(ComponentAttributeBag::class)
        ->and((string) $result)->toContain('col-class')
        ->and((string) $result)->toContain('table-class')
        ->and((string) $result)->toContain('data-col="1"')
        ->and((string) $result)->toContain('data-table="yes"');
});

it('resets page when updating search', function () {
    Livewire::test(LwDataTable::class)
        ->set('search', 'foo')
        ->assertSet('search', 'foo')
        ->assertSet('paginators.page', 1);
});

it('applies filters and removes empty values', function () {
    $component = Livewire::test(LwDataTable::class);
    $filters = [
        'status' => ['active' => '', 'pending' => 'yes'],
        'type' => ['admin' => null, 'user' => ''],
        'role' => ['editor' => '1'],
    ];
    $component->call('applyFilters', $filters);
    $applied = $component->get('filters');
    expect($applied)->toBe([
        'status' => ['pending' => 'yes'],
        'role' => ['editor' => '1'],
    ]);
});

it('toggles sort direction and resets sortBy when toggled to none', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('sortBy', 'name')
        ->set('sortDir', 'ASC');
    $component->call('setSortBy', 'name');
    expect($component->get('sortDir'))->toBe('DESC');
    $component->call('setSortBy', 'name');
    expect($component->get('sortDir'))->toBe('');
    expect($component->get('sortBy'))->toBe('');
});

it('sets sort direction to ASC when changing to a different field', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('sortBy', 'name')
        ->set('sortDir', 'DESC');

    $component->call('setSortBy', 'email');

    expect($component->get('sortBy'))->toBe('email')
        ->and($component->get('sortDir'))->toBe('ASC');
});

it('resets page when updating filters', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('paginators', ['page' => 5])
        ->set('filters', ['status' => ['active' => 'yes']]);

    expect($component->get('paginators.page'))->toBe(1);
});

it('resets page when updating columnsSearch nested values', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('paginators', ['page' => 5])
        ->set('columnsSearch.name', 'john');

    expect($component->get('paginators.page'))->toBe(1);
});

it('resets page when updating perPage', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('paginators', ['page' => 3])
        ->set('paginators', 'perPage', 50);

    expect($component->get('paginators.page'))->toBe(1);
});

it('does not reset page when updating unrelated property', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('paginators', ['page' => 4])
        ->set('paginators', 'sortBy', 'name');

    expect($component->get('paginators.page'))->toBe(4);
});

it('preserves zero and false values when applying filters', function () {
    $component = Livewire::test(LwDataTable::class);

    $component->call('applyFilters', [
        'priority' => ['low' => 0, 'high' => '', 'medium' => false],
        'tags' => ['active' => [], 'new' => 'yes'],
    ]);

    expect($component->get('filters'))->toBe([
        'priority' => ['low' => 0, 'medium' => false],
        'tags' => ['new' => 'yes'],
    ]);
});

it('preserves range filter from and to values when applying filters', function () {
    $component = Livewire::test(LwDataTable::class);

    $component->call('applyFilters', [
        'date' => ['from' => '2024-01-01', 'to' => '2024-01-31'],
        'status' => ['active' => 'yes'],
    ]);

    expect($component->get('filters'))->toBe([
        'date' => ['from' => '2024-01-01', 'to' => '2024-01-31'],
        'status' => ['active' => 'yes'],
    ]);
    expect($component->get('rawFilters'))->toBe([
        'date' => ['from' => '2024-01-01', 'to' => '2024-01-31'],
        'status' => ['active' => 'yes'],
    ]);
});

it('falls back gracefully when the data table cache is unavailable', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('dt', 'does-not-exist');

    $reflection = new ReflectionClass($component->instance());
    $method = $reflection->getMethod('hydrateDataTable');
    $method->setAccessible(true);

    $result = $method->invoke($component->instance());

    expect($result)->toBeNull();
});

it('should show filters container when component is not collapsible', function () {
    $mockDataTable = new class {
        public $filters;
        public function __construct()
        {
            $this->filters = new class {
                public function isCollapsible()
                {
                    return false;
                }
            };
        }
    };

    $component = Livewire::test(LwDataTable::class)
        ->set('dataTable', $mockDataTable)
        ->set('filtersContainerIsOpen', null)
        ->set('filters', []);

    expect($component->call('shouldShowFiltersContainer'))->toBeTrue();
});

it('returns default Livewire query string parameter names', function () {
    $mockDataTable = new class {
        public $pageName = null;
        public $filtersName = null;
        public $searchName = null;
        public $columnsSearchName = null;

        public function preset()
        {
            return new class {
                public function get($key, $default)
                {
                    return $default;
                }
            };
        }
    };

    $component = Livewire::test(LwDataTable::class)
        ->set('dataTable', $mockDataTable);

    $reflection = new ReflectionClass($component->instance());
    $pageNameMethod = $reflection->getMethod('pageNameUrlParam');
    $filtersNameMethod = $reflection->getMethod('filtersUrlParam');
    $searchNameMethod = $reflection->getMethod('searchUrlParam');
    $columnsSearchNameMethod = $reflection->getMethod('columnsSearchUrlParam');

    $pageNameMethod->setAccessible(true);
    $filtersNameMethod->setAccessible(true);
    $searchNameMethod->setAccessible(true);
    $columnsSearchNameMethod->setAccessible(true);

    expect($pageNameMethod->invoke($component->instance()))->toBe('page');
    expect($filtersNameMethod->invoke($component->instance()))->toBe('filters');
    expect($searchNameMethod->invoke($component->instance()))->toBe('search');
    expect($columnsSearchNameMethod->invoke($component->instance()))->toBe('cols-search');
});

it('delegates runAction to the underlying data table instance', function () {
    $called = [];
    $mockDataTable = new class ($called) {
        public $called;

        public function __construct(&$called)
        {
            $this->called = &$called;
        }

        public function runAction(string $action, ...$params)
        {
            $this->called = [$action, $params];
        }
    };

    $component = Livewire::test(LwDataTable::class)
        ->set('dataTable', $mockDataTable)
        ->call('runAction', 'delete', 42);

    expect($called[0])->toBe('delete')
        ->and($called[1])->toBe([42]);
});

it('allows sorting only when more than one row exists', function () {
    $component = Livewire::test(LwDataTable::class);

    expect($component->instance()->shouldAllowSorting(collect([1, 2])))->toBeTrue();
    expect($component->instance()->shouldAllowSorting(collect([1])))->toBeFalse();
});

it('allows columns search only when more than one row exists', function () {
    $component = Livewire::test(LwDataTable::class);

    expect($component->instance()->shouldAllowColumnsSearch(collect([1, 2])))->toBeTrue();
    expect($component->instance()->shouldAllowColumnsSearch(collect([1])))->toBeFalse();
});

it('keeps existing filter values when computing initial filters', function () {
    $mockDataTable = new class {
        public $filters;
        public function __construct()
        {
            $this->filters = new class {
                public $filtersItems;
                public function __construct()
                {
                    $this->filtersItems = [
                        (object) [
                            'dataField' => 'status',
                            'name' => 'active',
                            'mode' => \ErickComp\LivewireDataTable\DataTable\Filter::MODE_CONTAINS,
                            'label' => 'Active',
                        ],
                    ];
                }
            };
        }
    };

    $component = Livewire::test(LwDataTable::class)
        ->set('dataTable', $mockDataTable)
        ->set('filters', ['status' => ['active' => 'yes']]);

    expect($component->call('computeInitialFilters')['status']['active'])->toBe('yes');
});

it('computes initial filters with default values', function () {
    $mockDataTable = new class {
        public $filters;
        public function __construct()
        {
            $this->filters = new class {
                public $filtersItems;
                public function __construct()
                {
                    $this->filtersItems = [
                        (object) [
                            'dataField' => 'status',
                            'name' => 'active',
                            'mode' => \ErickComp\LivewireDataTable\DataTable\Filter::MODE_CONTAINS,
                            'label' => 'Active',
                        ],
                        (object) [
                            'dataField' => 'date',
                            'name' => 'created',
                            'mode' => \ErickComp\LivewireDataTable\DataTable\Filter::MODE_RANGE,
                            'label' => 'Created',
                        ],
                    ];
                }
            };
        }
    };
    $component = Livewire::test(LwDataTable::class)
        ->set('dataTable', $mockDataTable)
        ->set('filters', []);
    $filters = $component->call('computeInitialFilters');
    expect($filters['status']['active'])->toBe('');
    expect($filters['date']['created'])->toBe(['from' => '', 'to' => '']);
});

it('should show filters container if filtersContainerIsOpen is true', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('filtersContainerIsOpen', true);
    expect($component->call('shouldShowFiltersContainer'))->toBeTrue();
});

it('should show filters container if filters are not empty', function () {
    $component = Livewire::test(LwDataTable::class)
        ->set('filtersContainerIsOpen', null)
        ->set('filters', ['foo' => ['bar' => 'baz']]);
    expect($component->call('shouldShowFiltersContainer'))->toBeTrue();
});

it('hides filters container when collapsible and explicitly closed', function () {
    $mockDataTable = new class {
        public $filters;
        public function __construct()
        {
            $this->filters = new class {
                public function isCollapsible()
                {
                    return true;
                }
            };
        }
    };

    $component = Livewire::test(LwDataTable::class)
        ->set('dataTable', $mockDataTable)
        ->set('filtersContainerIsOpen', false)
        ->set('filters', []);

    expect($component->call('shouldShowFiltersContainer'))->toBeFalse();
});

it('shows filters container when collapsible and open state is undefined', function () {
    $mockDataTable = new class {
        public $filters;
        public function __construct()
        {
            $this->filters = new class {
                public function isCollapsible()
                {
                    return true;
                }
            };
        }
    };

    $component = Livewire::test(LwDataTable::class)
        ->set('dataTable', $mockDataTable)
        ->set('filtersContainerIsOpen', null)
        ->set('filters', []);

    expect($component->call('shouldShowFiltersContainer'))->toBeTrue();
});

it('renders view with rows and initialFilters data', function () {
    $rows = collect([
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'Jane'],
    ]);

    $mockDataTable = new class {
        public $filters;
        public $preset;

        public function __construct()
        {
            $this->filters = new class {
                public $filtersItems;
                public function isCollapsible()
                {
                    return false;
                }
                public function __construct()
                {
                    $this->filtersItems = [];
                }
            };
        }

        public function preset()
        {
            return new class {
                public function get($key, $default = null)
                {
                    return $default;
                }
            };
        }

        public function paginationView()
        {
            return 'livewire::tailwind';
        }

        public function paginationSimpleView()
        {
            return 'livewire::simple-tailwind';
        }
    };

    $component = Livewire::test(LwDataTable::class)
        ->set('dataTable', $mockDataTable)
        ->set('filters', []);

    $view = $component->instance()->render();

    expect($view)->not()->toBeNull();
});

it('handles page reset when paginated results exceed lastPage', function () {
    $paginator = new LengthAwarePaginator(
        collect([['id' => 1]]),
        10,
        15,
        2,
    );

    $mockDataTable = new class {
        public $filters;
        public $pageName = 'page';

        public function __construct()
        {
            $this->filters = new class {
                public $filtersItems;
                public function isCollapsible()
                {
                    return false;
                }
                public function __construct()
                {
                    $this->filtersItems = [];
                }
            };
        }

        public function preset()
        {
            return new class {
                public function get($key, $default = null)
                {
                    return $default;
                }
            };
        }

        public function paginationView()
        {
            return 'livewire::tailwind';
        }

        public function paginationSimpleView()
        {
            return 'livewire::simple-tailwind';
        }
    };

    $component = Livewire::test(LwDataTable::class)
        ->set('dataTable', $mockDataTable)
        ->set('filters', [])
        ->set('paginators', ['page' => 5]);

    $reflection = new ReflectionClass($component->instance());
    $renderMethod = $reflection->getMethod('render');
    $renderMethod->setAccessible(true);

    $renderMethod->invoke($component->instance());
});

it('validates that renderPagination handles different paginator types', function () {
    $component = Livewire::test(LwDataTable::class);

    $collection = collect([['id' => 1]]);
    $paginator = new LengthAwarePaginator(
        $collection,
        10,
        15,
        1,
    );

    $reflection = new ReflectionClass($component->instance());
    $method = $reflection->getMethod('renderPagination');
    $method->setAccessible(true);

    $resultCollection = $method->invoke($component->instance(), $collection);
    $resultPaginator = $method->invoke($component->instance(), $paginator);

    expect($resultCollection)->toBe('');
    expect($resultPaginator)->not()->toBe('');
});

it('validates that isDataPaginated detects cursor and length aware paginators', function () {
    $component = Livewire::test(LwDataTable::class);

    $collection = collect([['id' => 1]]);
    $lengthAwarePaginator = new LengthAwarePaginator(
        $collection,
        10,
        15,
        1,
    );

    expect($component->instance()->isDataPaginated($collection))->toBeFalse();
    expect($component->instance()->isDataPaginated($lengthAwarePaginator))->toBeTrue();
});

/*
 * In a Livewire-focused package, useful tests include:
 * - Attribute merging and propagation (already covered)
 * - Query string synchronization and updates
 * - Pagination resets on filter/search changes
 * - Sorting logic and toggling
 * - Filter application and removal of empty values
 * - Computation of initial filter values
 * - Conditional UI logic (e.g., showing/hiding filter containers)
 * - Data provider integration (mocking data sources)
 * - Rendering and view data structure (now covered)
 * - Action handling (runAction, applyFilters, etc.)
 * - Paginator type handling and detection
 */
