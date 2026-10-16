<?php

namespace ErickComp\LivewireDataTable\Support;

/**
 * Server-side code referenced from a data table definition, e.g. a callable data source or an action handler.
 *
 * Data table definitions are stored in a file cache (see DataTable::toCache()), so the callable must be serializable:
 * a function name, "Class::method", "Class@method", the name of an invokable class, a [class or object, method] array
 * or an invokable object. Closures are rejected. Classes are resolved through the container when called, and so are
 * the parameters the callable asks for (see call()).
 */
class ServerCallable
{
    public const TYPE_INVOKABLE = 'invokable';
    public const TYPE_INVOKABLE_CLASS = 'invokable_class';
    public const TYPE_NAMED_FUNCTION = 'named_function';
    public const TYPE_STATIC_METHOD = 'static_method';
    public const TYPE_INSTANCE_METHOD = 'instance_method';
    public const TYPE_STATIC_THROUGH_MAGIC_METHOD = 'static_through_magic';
    public const TYPE_INSTANCE_THROUGH_MAGIC_METHOD = 'instance_through_magic';

    protected string $type;

    /** @var string|array{0: string, 1: string}|object */
    protected string|array|object $callable;

    /**
     * @throws \ValueError when the value is a closure, or does not refer to anything callable
     */
    final public function __construct(mixed $callable)
    {
        if ($callable instanceof \Closure) {
            throw new \ValueError('A closure cannot be used here: it cannot be stored in the data table\'s cache. Use a function name, "Class@method", "Class::method", an invokable class or a [class, method] array instead');
        }

        $type = static::detectType($callable);

        if ($type === null) {
            throw new \ValueError('[' . static::describe($callable) . '] is not a callable. Use a function name, "Class@method", "Class::method", an invokable class or a [class, method] array');
        }

        $this->type = $type;
        $this->callable = static::normalize($callable, $type);
    }

    /**
     * Like the constructor, but returns null instead of throwing for a value that is not a usable callable
     */
    public static function tryFrom(mixed $callable): ?static
    {
        try {
            return new static($callable);
        } catch (\ValueError) {
            return null;
        }
    }

    public function type(): string
    {
        return $this->type;
    }

    /**
     * The callable in its normalized form: an invokable object, a function or class name, or a [class, method] array
     *
     * @return string|array{0: string, 1: string}|object
     */
    public function callable(): string|array|object
    {
        return $this->callable;
    }

    /**
     * Calls it through the container, which resolves the parameters it asks for: the given ones go by parameter name
     * (['option' => ...]) or by class name ([Selection::class => ...]), the rest is resolved from the container.
     * Methods reached through __call() or __callStatic() cannot tell their parameters, so they are called without any.
     *
     * @param array<string, mixed> $parameters
     */
    public function call(array $parameters = []): mixed
    {
        return match ($this->type) {
            static::TYPE_INVOKABLE, static::TYPE_NAMED_FUNCTION, static::TYPE_STATIC_METHOD => app()->call($this->callable, $parameters),
            static::TYPE_INVOKABLE_CLASS => app()->call([app()->make($this->callable), '__invoke'], $parameters),
            static::TYPE_INSTANCE_METHOD => app()->call([app()->make($this->callable[0]), $this->callable[1]], $parameters),
            static::TYPE_STATIC_THROUGH_MAGIC_METHOD => \call_user_func($this->callable),
            static::TYPE_INSTANCE_THROUGH_MAGIC_METHOD => \call_user_func([app()->make($this->callable[0]), $this->callable[1]]),
            default => throw new \LogicException("Unknown callable type [{$this->type}]"),
        };
    }

    public function __toString(): string
    {
        return static::describe($this->callable);
    }

    protected static function detectType(mixed $callable): ?string
    {
        if (\is_object($callable)) {
            return \method_exists($callable, '__invoke') ? static::TYPE_INVOKABLE : null;
        }

        if (\is_array($callable)) {
            if (\count($callable) !== 2 || !isset($callable[0], $callable[1]) || !\is_string($callable[1])) {
                return null;
            }

            [$target, $method] = $callable;

            if (\is_object($target)) {
                if (static::isPublicMethod($target, $method)) {
                    return static::TYPE_INSTANCE_METHOD;
                }

                return \is_callable([$target, $method]) ? static::TYPE_INSTANCE_THROUGH_MAGIC_METHOD : null;
            }

            return \is_string($target) ? static::detectClassMethodType($target, $method) : null;
        }

        if (!\is_string($callable) || \trim($callable) === '') {
            return null;
        }

        $callable = \trim($callable);

        if (\function_exists($callable)) {
            return static::TYPE_NAMED_FUNCTION;
        }

        $callable = \str_replace('@', '::', $callable);

        if (\str_contains($callable, '::')) {
            [$class, $method] = \explode('::', $callable, 2);

            return static::detectClassMethodType($class, $method);
        }

        return \class_exists($callable) && \method_exists($callable, '__invoke')
            ? static::TYPE_INVOKABLE_CLASS
            : null;
    }

    protected static function detectClassMethodType(string $class, string $method): ?string
    {
        if (!\class_exists($class) || $method === '') {
            return null;
        }

        if (static::isPublicMethod($class, $method)) {
            // An instance method is called on an instance made by the container
            return (new \ReflectionMethod($class, $method))->isStatic()
                ? static::TYPE_STATIC_METHOD
                : static::TYPE_INSTANCE_METHOD;
        }

        // Not a public method: it may still be reached through __callStatic() or __call()
        if (\is_callable([$class, $method])) {
            return static::TYPE_STATIC_THROUGH_MAGIC_METHOD;
        }

        try {
            $object = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        } catch (\ReflectionException|\Error) {
            return null;
        }

        return \is_callable([$object, $method]) ? static::TYPE_INSTANCE_THROUGH_MAGIC_METHOD : null;
    }

    protected static function isPublicMethod(string|object $classOrObject, string $method): bool
    {
        return \method_exists($classOrObject, $method) && (new \ReflectionMethod($classOrObject, $method))->isPublic();
    }

    /**
     * @return string|array{0: string, 1: string}|object
     */
    protected static function normalize(string|array|object $callable, string $type): string|array|object
    {
        if ($type === static::TYPE_INVOKABLE) {
            return $callable;
        }

        if (\is_string($callable)) {
            $callable = \str_replace('@', '::', \trim($callable));

            return \str_contains($callable, '::') ? \explode('::', $callable, 2) : $callable;
        }

        // A [object, method] array keeps only the class: the object is made by the container when called
        return [\is_object($callable[0]) ? $callable[0]::class : $callable[0], $callable[1]];
    }

    protected static function describe(mixed $callable): string
    {
        return match (true) {
            \is_string($callable) => $callable,
            \is_array($callable) && \count($callable) === 2 && isset($callable[0], $callable[1]) && \is_string($callable[1]) => (\is_object($callable[0]) ? $callable[0]::class : (string) $callable[0]) . '::' . $callable[1],
            \is_object($callable) => $callable::class,
            default => \get_debug_type($callable),
        };
    }
}
