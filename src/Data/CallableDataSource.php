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
    protected const CALLABLE_TYPE_CLOSURE = 'closure';
    protected const CALLABLE_TYPE_INVALID = 'invalid';
    protected const CALLABLE_TYPE_INVOKABLE = 'invokable';
    protected const CALLABLE_TYPE_NAMED_FUNCTION = 'named_function';
    protected const CALLABLE_TYPE_STATIC_METHOD = 'static_method';
    protected const CALLABLE_TYPE_INSTANCE_METHOD = 'instance_method';

    protected string|callable|SerializableClosure $dataSource;
    protected string $callableType;


    public function __construct(
        string|callable $dataSource,
        protected DataSourcePaginationType $paginationType,
    ) {
        $originalDataSource = $dataSource;
        $callableType = static::CALLABLE_TYPE_INVALID;

        if ($dataSource instanceof \Closure) {
            $dataSource = new SerializableClosure($dataSource);
            $callableType = static::CALLABLE_TYPE_CLOSURE;

        } elseif (\is_object($dataSource) && \method_exists($dataSource, '__invoke')) {
            $callableType = static::CALLABLE_TYPE_INVOKABLE;
        } elseif (\is_callable($dataSource) && \is_string($dataSource)) {
            if (\str_contains('::', $dataSource)) {
                $callableType = static::CALLABLE_TYPE_STATIC_METHOD;
                $dataSource = \explode('::', $dataSource);
            } else {
                $callableType = static::CALLABLE_TYPE_NAMED_FUNCTION;
            }
        } elseif (\is_callable($dataSource)) {
            // it's an array for sure
            $callableType = \is_object($dataSource[0])
                ? static::CALLABLE_TYPE_INSTANCE_METHOD
                : static::CALLABLE_TYPE_STATIC_METHOD;
        } elseif (\is_string($dataSource)) {
            // not callable at all

            // If the given callback is using the format <class>::<method> but is not a callable,
            // it means it could be an instance method and not a static one, so let's try changing the :: for @
            // for the Laravel style callbacks
            if (\str_contains('::')) {
                $dataSource = \str_replace('::', '@', $dataSource);
            }

            // Laravel-style callbacks with "@" sign
            $dataSource = Str::parseCallback($dataSource);

            if (!\is_callable($dataSource)) {
                // If it's still not a callable, let's check if we create an object of such class, it will be callable
                [$class, $method] = $dataSource;

                if (\class_exists($class)) {
                    $object = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

                    if (\is_callable([$object, $method])) {
                        $callableType = static::CALLABLE_TYPE_INSTANCE_METHOD;
                    }
                }
            }
        }

        if ($callableType === static::CALLABLE_TYPE_INVALID) {

            $debugValue = \is_string($originalDataSource)
                ? $originalDataSource
                : \var_export($originalDataSource, true);

            $errmsg = "Cannot create a callable data source from the give value [$debugValue]";
            throw new \ValueError($errmsg);
        }

    }
    public function getData(LwDataRetrievalParams $params): Paginator|LengthAwarePaginator|CursorPaginator|Collection {}
}
