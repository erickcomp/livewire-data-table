<?php

namespace ErickComp\LivewireDataTable\Selection;

use ErickComp\LivewireDataTable\Data\ProvidesUnpaginatedData;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Rows of a data table picked by the user, resolved against its data source.
 *
 * In the "ids" mode, the rows are the ones whose identity column holds one of the given ids. In the "all" mode, they
 * are all the rows matching the table's current search, filters and columns search, across every page, except the
 * ones whose identity is among the given (excluded) ids.
 *
 * Both modes go through the data source with the current search and filters applied, so an id that is not among
 * the table's rows (made up by the client, or filtered out meanwhile) never makes it into the selection.
 */
class Selection
{
    public const MODE_IDS = 'ids';
    public const MODE_ALL = 'all';

    /** @var string[] */
    protected array $ids;

    protected LwDataRetrievalParams $params;

    protected EloquentBuilder|QueryBuilder|Collection|LazyCollection|null $unpaginatedData = null;

    /**
     * @param string $mode One of the MODE_* constants
     * @param iterable<mixed> $ids The selected ids in the "ids" mode, the excluded ones in the "all" mode
     */
    public function __construct(
        protected ProvidesUnpaginatedData $dataSource,
        LwDataRetrievalParams $params,
        protected string $identityColumn,
        protected string $mode,
        iterable $ids = [],
    ) {
        if (!\in_array($mode, [static::MODE_IDS, static::MODE_ALL], true)) {
            throw new \ValueError("Invalid selection mode: [$mode]. The valid modes are: \"" . static::MODE_IDS . '" and "' . static::MODE_ALL . '"');
        }

        $this->params = $params->withoutPagination();
        $this->ids = static::normalizeIds($ids);
    }

    /**
     * Ids as unique, non-empty strings, which is how they travel to and from the browser. Values that cannot stand
     * for an id (null, booleans, arrays, objects that are not Stringable) are dropped.
     *
     * @param iterable<mixed> $ids
     * @return string[]
     */
    public static function normalizeIds(iterable $ids): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            if (\is_int($id) || \is_float($id) || \is_string($id) || $id instanceof \Stringable) {
                $id = (string) $id;

                if ($id !== '') {
                    $normalized[$id] = $id;
                }
            }
        }

        return \array_values($normalized);
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function isAllMatching(): bool
    {
        return $this->mode === static::MODE_ALL;
    }

    /**
     * The ids picked in the "ids" mode, as given (not checked against the data source). Empty in the "all" mode.
     *
     * @return string[]
     */
    public function selectedIds(): array
    {
        return $this->isAllMatching() ? [] : $this->ids;
    }

    /**
     * The ids left out in the "all" mode, as given. Empty in the "ids" mode.
     *
     * @return string[]
     */
    public function excludedIds(): array
    {
        return $this->isAllMatching() ? $this->ids : [];
    }

    public function identityColumn(): string
    {
        return $this->identityColumn;
    }

    /**
     * Whether the data source is backed by a query, which makes query() available.
     */
    public function isQueryable(): bool
    {
        $data = $this->unpaginatedData();

        return $data instanceof EloquentBuilder || $data instanceof QueryBuilder;
    }

    /**
     * A query for the selected rows, sorted like the table: the data source's query with the current search, filters
     * and sorting, constrained to the selected ids (or to everything but the excluded ones).
     *
     * @throws \LogicException when the data source is not backed by a query
     */
    public function query(): EloquentBuilder|QueryBuilder
    {
        $data = $this->unpaginatedData();

        if (!$data instanceof EloquentBuilder && !$data instanceof QueryBuilder) {
            throw new \LogicException(
                'Cannot build a query for the selected rows: the data table\'s data source is not backed by a query builder ('
                . \get_debug_type($data) . ' given). Use rows(), ids() or lazy() instead.',
            );
        }

        $column = $this->queryColumn($data);
        $ids = $this->idsFitForQuery($data);

        if (!$this->isAllMatching()) {
            return $data->whereIn($column, $ids);
        }

        if (!empty($ids)) {
            $data->whereNotIn($column, $ids);
        }

        return $data;
    }

    /**
     * The selected rows, sorted like the table.
     */
    public function rows(): Collection|LazyCollection
    {
        if ($this->isEmptyWithoutLookup()) {
            return collect();
        }

        $data = $this->unpaginatedData();

        if ($data instanceof EloquentBuilder || $data instanceof QueryBuilder) {
            return $this->query()->get();
        }

        $ids = \array_flip($this->ids);
        $isListed = fn(mixed $row): bool => isset($ids[$this->rowIdentity($row)]);

        return $this->isAllMatching()
            ? $data->reject($isListed)->values()
            : $data->filter($isListed)->values();
    }

    /**
     * The selected rows, loaded in chunks as the collection is iterated, sorted like the table. Meant for reading
     * them (exports, for instance): the chunks are taken by offset, so changing the rows while iterating may skip
     * some of them. To change the rows, see lazyById().
     */
    public function lazy(int $chunkSize = 1000): LazyCollection
    {
        if ($this->isEmptyWithoutLookup()) {
            return LazyCollection::empty();
        }

        if (!$this->isQueryable()) {
            return LazyCollection::make(fn() => yield from $this->rows());
        }

        $query = $this->query();

        // Offset chunks need a total order: the identity column breaks the ties left by the table's sorting
        return $query->orderBy($this->queryColumn($query))->lazy($chunkSize);
    }

    /**
     * The selected rows, loaded in chunks by their identity column as the collection is iterated. Unlike lazy(), it is
     * safe to change the rows (even the columns the table is filtered by) while iterating, but the table's sorting is
     * not kept: the rows come in identity order.
     */
    public function lazyById(int $chunkSize = 1000): LazyCollection
    {
        if ($this->isEmptyWithoutLookup()) {
            return LazyCollection::empty();
        }

        if (!$this->isQueryable()) {
            return LazyCollection::make(fn() => yield from $this->rows());
        }

        $query = $this->query()->reorder();
        $column = $this->queryColumn($query);

        return $query->lazyById($chunkSize, $column, $this->unqualifiedIdentityColumn());
    }

    /**
     * The ids of the selected rows, as strings, sorted like the table. Unlike selectedIds(), they are checked
     * against the data source: an id that is not among the table's rows is not returned.
     *
     * @return string[]
     */
    public function ids(): array
    {
        if ($this->isEmptyWithoutLookup()) {
            return [];
        }

        if ($this->isQueryable()) {
            $query = $this->query();

            return static::normalizeIds($query->pluck($this->queryColumn($query)));
        }

        return static::normalizeIds($this->rows()->map(fn(mixed $row): string => $this->rowIdentity($row)));
    }

    public function count(): int
    {
        if ($this->isEmptyWithoutLookup()) {
            return 0;
        }

        if ($this->isQueryable()) {
            $query = $this->query();

            // Counted like the paginator counts the table's rows: unlike count(), it also gets grouped queries right
            return ($query instanceof EloquentBuilder ? $query->toBase() : $query)->getCountForPagination();
        }

        return $this->rows()->count();
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @return array{mode: string, ids: string[]}
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'ids' => $this->ids,
        ];
    }

    /**
     * Asked to the data source once (it may call a user callable, or filter a whole collection). Queries are handed
     * out as copies, so constraining one never changes the next.
     */
    protected function unpaginatedData(): EloquentBuilder|QueryBuilder|Collection|LazyCollection
    {
        $this->unpaginatedData ??= $this->dataSource->getUnpaginatedData($this->params);

        return $this->unpaginatedData instanceof EloquentBuilder || $this->unpaginatedData instanceof QueryBuilder
            ? clone $this->unpaginatedData
            : $this->unpaginatedData;
    }

    /**
     * The "ids" mode with no ids selects nothing: no need to go to the data source to know that.
     */
    protected function isEmptyWithoutLookup(): bool
    {
        return !$this->isAllMatching() && empty($this->ids);
    }

    protected function rowIdentity(mixed $row): string
    {
        $identity = \data_get($row, $this->identityColumn);

        return \is_scalar($identity) || $identity instanceof \Stringable ? (string) $identity : '';
    }

    /**
     * The identity column as the query refers to it: qualified with the model's table on Eloquent queries, so it
     * keeps pointing to the right column when the query joins other tables.
     */
    protected function queryColumn(EloquentBuilder|QueryBuilder $query): string
    {
        return $query instanceof EloquentBuilder
            ? $query->qualifyColumn($this->identityColumn)
            : $this->identityColumn;
    }

    protected function unqualifiedIdentityColumn(): string
    {
        $segments = \explode('.', $this->identityColumn);

        return \end($segments);
    }

    /**
     * When the identity column is the integer primary key of an Eloquent model, ids that are not integers are
     * dropped: they cannot match any row, and some databases (PostgreSQL) fail the whole query when such a value is
     * compared to an integer column.
     *
     * @return string[]
     */
    protected function idsFitForQuery(EloquentBuilder|QueryBuilder $query): array
    {
        if (!$query instanceof EloquentBuilder) {
            return $this->ids;
        }

        $model = $query->getModel();

        $identityIsIntegerKey = $this->unqualifiedIdentityColumn() === $model->getKeyName()
            && \in_array($model->getKeyType(), ['int', 'integer'], true);

        if (!$identityIsIntegerKey) {
            return $this->ids;
        }

        return \array_values(\array_filter($this->ids, fn(string $id): bool => \preg_match('/^-?\d+$/', $id) === 1));
    }
}
