<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Concerns\AppliesDataRetrievalParamsOnCollections;
use ErickComp\LivewireDataTable\Concerns\PaginatesCollections;
use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

class CallableDataSource implements DataSource
{
    protected const CALLABLE_TYPE_INVALID = 'invalid';
    protected const CALLABLE_TYPE_CLOSURE = 'closure';
    protected const CALLABLE_TYPE_INVOKABLE = 'invokable';
    protected const CALLABLE_TYPE_NAMED_FUNCTION = 'named_function';
    protected const CALLABLE_TYPE_STATIC_METHOD = 'static_method';
    protected const CALLABLE_TYPE_INSTANCE_METHOD = 'instance_method';
    protected const CALLABLE_TYPE_STATIC_THROUGH_MAGIC_METHOD = 'static_through_magic';
    protected const CALLABLE_TYPE_INSTANCE_THROUGH_MAGIC_METHOD = 'instance_through_magic';
    /** @var string|callable */
    protected string|array|object $dataSource;
    protected string $callableType;

    public function __construct(
        string|callable $dataSource,
        protected DataSourcePaginationType $paginationType,
    ) {
        $callableType = $this->parseCallableType($dataSource);

        if ($callableType === static::CALLABLE_TYPE_INVALID) {

            $debugValue = \is_string($dataSource)
                ? $dataSource
                : \var_export($dataSource, true);

            $errmsg = "Cannot create a callable data source from the give value [$debugValue]";
            throw new \ValueError($errmsg);
        }

        //@TODO: Check if there's a safe way to allow closures to be used without any caveats
        if ($callableType === static::CALLABLE_TYPE_CLOSURE) {
            //return new SerializableClosure($dataSource);
            $errmsg = 'Cannot use a closure as the data source for the component';

            throw new \ValueError($errmsg);
        }

        $this->callableType = $callableType;
        $this->dataSource = $this->normalizeDataSource($dataSource);

    }

    protected function parseCallableType(string|callable $dataSource): string
    {
        if ($dataSource instanceof \Closure) {
            return static::CALLABLE_TYPE_CLOSURE;
        }

        if (\is_object($dataSource) && \method_exists($dataSource, '__invoke')) {
            return static::CALLABLE_TYPE_INVOKABLE;
        }

        if (\is_array($dataSource) && \is_callable($dataSource)) {
            if (\is_object($dataSource[0])) {
                return \method_exists($dataSource[0], $dataSource[1])
                    ? static::CALLABLE_TYPE_INSTANCE_METHOD
                    : static::CALLABLE_TYPE_INSTANCE_THROUGH_MAGIC_METHOD;
            }

            return \method_exists($dataSource[0], $dataSource[1])
                ? static::CALLABLE_TYPE_STATIC_METHOD
                : static::CALLABLE_TYPE_STATIC_THROUGH_MAGIC_METHOD;
        }

        if (\is_string($dataSource)) {
            if (\function_exists($dataSource)) {
                return static::CALLABLE_TYPE_NAMED_FUNCTION;
            }

            // Let's work with "::" only
            $dataSource = \str_replace('@', '::', $dataSource);

            if (\str_contains($dataSource, '::')) {
                [$class, $method] = \explode('::', $dataSource, 2);

                if (!\class_exists($class)) {
                    return static::CALLABLE_TYPE_INVALID;
                }

                if (\is_callable($dataSource)) {
                    return \method_exists($class, $method)
                        ? static::CALLABLE_TYPE_STATIC_METHOD
                        : static::CALLABLE_TYPE_STATIC_THROUGH_MAGIC_METHOD;
                }

                // If it's not callable using <class>::<method> syntax, let's try to check if it's instance callable
                $object = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
                if (\is_callable([$object, $method])) {
                    return \method_exists($object, $method)
                        ? static::CALLABLE_TYPE_INSTANCE_METHOD
                        : static::CALLABLE_TYPE_INSTANCE_THROUGH_MAGIC_METHOD;
                }
            }
        }

        return static::CALLABLE_TYPE_INVALID;
    }

    protected function normalizeDataSource(callable|string $dataSource): callable|array
    {
        if ($this->callableType === static::CALLABLE_TYPE_INVOKABLE) {
            return $dataSource;
        }

        if ($this->callableType === static::CALLABLE_TYPE_NAMED_FUNCTION) {
            return $dataSource;
        }

        if (
            !\in_array(
                $this->callableType,
                [
                    static::CALLABLE_TYPE_STATIC_METHOD,
                    static::CALLABLE_TYPE_STATIC_THROUGH_MAGIC_METHOD,
                    static::CALLABLE_TYPE_INSTANCE_METHOD,
                    static::CALLABLE_TYPE_INSTANCE_THROUGH_MAGIC_METHOD,
                ],
            )
        ) {
            $errmsg = 'Could not normalize dataSource value from value [' . (\is_string($dataSource) ? $dataSource : \var_export($dataSource, true)) . ']';

            throw new \ValueError($errmsg);
        }

        if (\is_string($dataSource)) {
            $dataSource = \str_replace('@', '::', $dataSource);

            if (\str_contains($dataSource, '::')) {
                return \explode('::', $dataSource, 2);
            }
        }

        if (\is_array($dataSource)) {
            if (\is_object($dataSource[0])) {
                $dataSource[0] = \get_class($dataSource[0]);
            }

            return $dataSource;
        }

        $errmsg = 'Could not normalize dataSource value from value [' . (\var_export($dataSource, true)) . ']';

        throw new \ValueError($errmsg);
    }

    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection|LazyCollection
    {
        $data = $this->getDataFromCallable($params);

        return $data instanceof LazyCollection
            ? $data
            : $this->paginate($data, $params);
    }

    protected function paginate(
        Paginator|LengthAwarePaginator|CursorPaginator|QueryBuilder|EloquentBuilder|Collection $data,
        LwDataRetrievalParams $params,
    ): Paginator|LengthAwarePaginator|CursorPaginator|Collection|LazyCollection {
        if ($data instanceof Collection) {
            if ($this->paginationType === DataSourcePaginationType::Cursor) {
                Log::notice("erickcomp/livewire-data-table: Static data for data table is using \"cursor\" pagination type, which can't be done. Simple pagination will be used");
            }

            return match ($this->paginationType) {
                DataSourcePaginationType::None => $data,
                DataSourcePaginationType::LengthAware => $params->paginate($data),
                DataSourcePaginationType::Simple, DataSourcePaginationType::Cursor => $params->simplePaginate($data),
            };
        }

        if ($data instanceof QueryBuilder || $data instanceof EloquentBuilder) {
            return match ($this->paginationType) {
                DataSourcePaginationType::None => $data->get(),
                DataSourcePaginationType::LengthAware => $params->paginate($data),
                DataSourcePaginationType::Cursor => $params->cursorPaginate($data),
                DataSourcePaginationType::Simple => $params->simplePaginate($data),
            };
        }

        return $data;
    }

    protected function getDataFromCallable(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection|LazyCollection|QueryBuilder|EloquentBuilder
    {

        $data = match ($this->callableType) {
            static::CALLABLE_TYPE_INVALID => throw new \LogicException('Cannot get data from invalid data source: [' . (\is_string($this->dataSource) ? $this->dataSource : \var_export($this->dataSource, true)) . ']'),
            static::CALLABLE_TYPE_CLOSURE => throw new \ValueError('Cannot use a closure as the data source for the component'),
            static::CALLABLE_TYPE_INVOKABLE, static::CALLABLE_TYPE_NAMED_FUNCTION, static::CALLABLE_TYPE_STATIC_METHOD => app()->call($this->dataSource, [LwDataRetrievalParams::class => $params]),
            static::CALLABLE_TYPE_INSTANCE_METHOD => app()->call([app()->make($this->dataSource[0]), $this->dataSource[1]], [LwDataRetrievalParams::class => $params]),
            static::CALLABLE_TYPE_STATIC_THROUGH_MAGIC_METHOD => \call_user_func($this->dataSource),
            static::CALLABLE_TYPE_INSTANCE_THROUGH_MAGIC_METHOD => \call_user_func([app()->make($this->dataSource[0]), $this->dataSource[1]]),
            default => throw new \ValueError("Cannot get data from callable type [{$this->callableType}]"),
        };

        if (
            $data instanceof Paginator
            || $data instanceof LengthAwarePaginator
            || $data instanceof CursorPaginator
            || $data instanceof Collection
            || $data instanceof LazyCollection
            || $data instanceof QueryBuilder
            || $data instanceof EloquentBuilder
        ) {
            return $data;
        }

        if (!\is_iterable($data)) {
            $types = \implode(
                '|',
                [
                    Paginator::class,
                    LengthAwarePaginator::class,
                    CursorPaginator::class,
                    Collection::class,
                    'iterable',
                ],
            );

            throw new \UnexpectedValueException('Data source callable must return ' . $types . ', ' . get_debug_type($data) . ' returned');
        }

        return collect($data);
    }
}
