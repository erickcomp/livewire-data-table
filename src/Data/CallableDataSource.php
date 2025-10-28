<?php

namespace ErickComp\LivewireDataTable\Data;

use ErickComp\LivewireDataTable\Livewire\LwDataRetrievalParams;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\SerializableClosure\SerializableClosure;
use Illuminate\Support\Str;

class CallableDataSource implements DataSource
{
    protected const CALLABLE_TYPE_INVALID = 'invalid';
    protected const CALLABLE_TYPE_CLOSURE = 'closure';
    protected const CALLABLE_TYPE_INVOKABLE = 'invokable';
    protected const CALLABLE_TYPE_NAMED_FUNCTION = 'named_function';
    protected const CALLABLE_TYPE_STATIC_METHOD = 'static_method';
    protected const CALLABLE_TYPE_INSTANCE_METHOD = 'instance_method';

    protected string|callable $dataSource;
    protected string $callableType;

    public function __construct(
        string|callable $dataSource,
        protected DataSourcePaginationType $paginationType,
        string $componentName,
    ) {
        $callableType = $this->parseCallableType($dataSource);

        if ($callableType === static::CALLABLE_TYPE_INVALID) {

            $debugValue = \is_string($dataSource)
                ? $dataSource
                : \var_export($dataSource, true);

            $errmsg = "Cannot create a callable data source from the give value [$debugValue]";
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
            return \is_object($dataSource[0])
                ? static::CALLABLE_TYPE_INSTANCE_METHOD
                : static::CALLABLE_TYPE_STATIC_METHOD;
        }

        if (\is_string($dataSource)) {
            if (\function_exists($dataSource)) {
                return static::CALLABLE_TYPE_NAMED_FUNCTION;
            }

            if (\str_contains($dataSource, '::')) {
                if (\is_callable($dataSource)) {
                    return static::CALLABLE_TYPE_STATIC_METHOD;
                }

                $dataSource = \str_replace('::', '@', $dataSource);
            }

            if (\str_contains($dataSource, '@')) {
                [$class, $method] = \explode('@', $dataSource, 2);

                if (!\class_exists($class)) {
                    return static::CALLABLE_TYPE_INVALID;
                }

                if (\is_callable([$class, $method])) {
                    return static::CALLABLE_TYPE_STATIC_METHOD;
                }

                $object = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

                if (\is_callable([$object, $method])) {
                    return static::CALLABLE_TYPE_INSTANCE_METHOD;
                }
            }
        }

        return static::CALLABLE_TYPE_INVALID;
    }

    protected function normalizeDataSource(callable|string $dataSource): SerializableClosure|string|array
    {
        //@TODO: Check if there's a safe way to allow closures to be used without 
        if ($this->callableType === static::CALLABLE_TYPE_CLOSURE) {
            //return new SerializableClosure($dataSource);
            $errmsg = 'Cannot use a closure as the data source for the component';

            throw new \ValueError($errmsg);
        }

        if ($this->callableType === static::CALLABLE_TYPE_INVOKABLE) {
            return $dataSource;
        }

        if ($this->callableType === static::CALLABLE_TYPE_NAMED_FUNCTION) {
            return $dataSource;
        }

        if ($this->callableType === static::CALLABLE_TYPE_STATIC_METHOD) {
            if (\is_array($dataSource)) {
                return $dataSource;
            }

            $dataSource = \str_replace('@', '::', $dataSource);

            if (\str_contains($dataSource, '::')) {
                return \explode('::', $dataSource, 2);
            }

            $errmsg = 'Could not evaluate static method callable from value [' . (\is_string($dataSource) ? $dataSource : \var_export($dataSource, true)) . ']';

            throw new \ValueError($errmsg);
        }

        if ($this->callableType === static::CALLABLE_TYPE_INSTANCE_METHOD) {
            if (\is_array($dataSource)) {
                if (\is_object($dataSource[0])) {
                    $dataSource[0] = \get_class($dataSource[0]);
                }
            } else {
                $dataSource = \str_replace('@', '::', $dataSource);
                $dataSource = \explode('::', $dataSource, 2);
            }

            return $dataSource;
        }

        $errmsg = 'Could not normalize dataSource value from value [' . (\is_string($dataSource) ? $dataSource : \var_export($dataSource, true)) . ']';

        throw new \ValueError($errmsg);
    }

    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection
    {
        if ($this->callableType === static::CALLABLE_TYPE_INVALID) {
            throw new \LogicException('Cannot get data from invalid data source: [' . (\is_string($this->dataSource) ? $this->dataSource : \var_export($this->dataSource, true)) . ']');
        }

        if ($this->callableType === static::CALLABLE_TYPE_CLOSURE) {
            return app()->call($this->dataSource->getClosure());
        }

        if ($this->callableType === static::CALLABLE_TYPE_INVOKABLE) {
            return app()->call($this->dataSource);
        }

        if ($this->callableType === static::CALLABLE_TYPE_NAMED_FUNCTION) {
            return app()->call($this->dataSource);
        }

        if ($this->callableType === static::CALLABLE_TYPE_STATIC_METHOD) {
            return app()->call($this->dataSource);
        }

        if ($this->callableType === static::CALLABLE_TYPE_INSTANCE_METHOD) {
            $instance = app()->make($this->dataSource[0]);
            return app()->call([$instance, $this->dataSource[1]]);
        }

        $errmsg = "Cannot get data from callable type [{$this->callableType}]";

        throw new \ValueError($errmsg);
    }
}
