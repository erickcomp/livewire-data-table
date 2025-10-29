<?php
namespace ErickComp\LivewireDataTable\Data;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use ErickComp\LivewireDataTable\Data\EloquentBuilderDataSource;

class DataSourceFactory
{
    public static function new(): static
    {
        return new static();
    }

    public function make(
        string|iterable|Collection|QueryBuilder|EloquentBuilder|callable|null $dataSource,
        DataSourcePaginationType $paginationType,
    ): DataSource {
        if (\is_string($dataSource)) {
            $dataSource = \trim($dataSource);
        }

        return match (true) {
            empty($dataSource) => new EmptyDataSource(),
            $this->isEloquentModel($dataSource) => $this->makeEloquentDataSource($dataSource, $paginationType),
            $this->isCallable($dataSource) => $this->makeCallableDataSource($dataSource, $paginationType),
            $dataSource instanceof QueryBuilder => $this->makeQueryBuilderDataSource($dataSource, $paginationType),
            $dataSource instanceof EloquentBuilder => $this->makeEloquentBuilderDataSource($dataSource, $paginationType),
            $dataSource instanceof Collection => $this->makeIterableDataSource($dataSource, $paginationType),
            \is_iterable($dataSource) => $this->makeIterableDataSource($dataSource, $paginationType),

            default => throw new \LogicException('Cannot create a data source from [' . (\is_string($dataSource) ? $dataSource : \var_export($dataSource, true)) . ']')
        };
    }

    protected function isEloquentModel($dataSource): bool
    {
        if ($dataSource instanceof EloquentModel) {
            return true;
        }

        if (!\is_string($dataSource)) {
            return false;
        }

        return \is_a($dataSource, EloquentModel::class, true);
    }

    protected function isCallable($dataSource): bool
    {
        if (\is_callable($dataSource)) {
            return true;
        }

        if (!\is_string($dataSource)) {
            return false;
        }

        // If the callback is using the format <class>::<method> but is not a callable,
        // it means it could be an instance method and not a static one, so let's try changing the :: for @
        $dataSource = \str_replace('::', '@', $dataSource);

        // Laravel-style callbacks with "@" sign
        $callable = Str::parseCallback($dataSource);

        if (\is_callable($callable)) {
            return true;
        }

        // If it's not callable, let's check if we create an object of such class, it will be callable
        [$class, $method] = $callable;

        if (!\class_exists($class)) {
            return false;
        }

        $object = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        return \is_callable([$object, $method]);
    }

    protected function makeEloquentDataSource(string|EloquentModel $dataSource, DataSourcePaginationType $paginationType)
    {
        if ($dataSource instanceof EloquentModel) {
            $dataSource = $dataSource::class;
        }

        return new EloquentDataSource($dataSource, $paginationType);
    }

    protected function makeCallableDataSource(string|callable $dataSource, DataSourcePaginationType $paginationType)
    {
        return new CallableDataSource($dataSource, $paginationType);
    }

    protected function makeQueryBuilderDataSource(QueryBuilder $query, DataSourcePaginationType $paginationType): QueryBuilderDataSource
    {
        return new QueryBuilderDataSource($query, $paginationType);
    }

    protected function makeEloquentBuilderDataSource(EloquentBuilder $query, DataSourcePaginationType $paginationType): EloquentBuilderDataSource
    {
        return new EloquentBuilderDataSource($query, $paginationType);
    }

    protected static function makeIterableDataSource(iterable $iterable, DataSourcePaginationType $paginationType)
    {
        return new IterableDataSource($iterable, $paginationType);
    }
}
