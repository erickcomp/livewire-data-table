<?php

use ErickComp\LivewireDataTable\Support\ServerCallable;
use Illuminate\Support\Collection;
use Tests\Fixtures\TestServerCallables;
use Tests\Fixtures\TestStaticMagicCallables;

it('calls each kind of callable, resolving its parameters by name and by class', function (mixed $callable, string $type, string $expected) {
    $serverCallable = new ServerCallable($callable);

    expect($serverCallable->type())->toBe($type)
        ->and($serverCallable->call(['option' => 'x', Collection::class => collect([1, 2])]))->toBe($expected);
})->with([
    'Class::staticMethod' => [TestServerCallables::class . '::staticMethod', ServerCallable::TYPE_STATIC_METHOD, 'static:x:2'],
    'Class@staticMethod' => [TestServerCallables::class . '@staticMethod', ServerCallable::TYPE_STATIC_METHOD, 'static:x:2'],
    'Class@instanceMethod' => [TestServerCallables::class . '@instanceMethod', ServerCallable::TYPE_INSTANCE_METHOD, 'instance:x:made-by-container'],
    'Class::instanceMethod' => [TestServerCallables::class . '::instanceMethod', ServerCallable::TYPE_INSTANCE_METHOD, 'instance:x:made-by-container'],
    '[class, method]' => [[TestServerCallables::class, 'instanceMethod'], ServerCallable::TYPE_INSTANCE_METHOD, 'instance:x:made-by-container'],
    '[object, method]' => [[new TestServerCallables(), 'instanceMethod'], ServerCallable::TYPE_INSTANCE_METHOD, 'instance:x:made-by-container'],
    'invokable class' => [TestServerCallables::class, ServerCallable::TYPE_INVOKABLE_CLASS, 'invoked:x'],
    'invokable object' => [new TestServerCallables(), ServerCallable::TYPE_INVOKABLE, 'invoked:x'],
    'magic instance method' => [TestServerCallables::class . '@somethingElse', ServerCallable::TYPE_INSTANCE_THROUGH_MAGIC_METHOD, 'magic-instance:somethingElse:0'],
    'magic static method' => [TestStaticMagicCallables::class . '::somethingElse', ServerCallable::TYPE_STATIC_THROUGH_MAGIC_METHOD, 'magic-static:somethingElse:0'],
]);

it('calls a function by name', function () {
    // The container hands unused parameters over as extra arguments, which PHP's own functions refuse: only "string"
    $serverCallable = new ServerCallable('strtoupper');

    expect($serverCallable->type())->toBe(ServerCallable::TYPE_NAMED_FUNCTION)
        ->and($serverCallable->call(['string' => 'x']))->toBe('X');
});

it('normalizes the callable into a serializable form', function (mixed $callable, mixed $expected) {
    $serverCallable = new ServerCallable($callable);

    expect($serverCallable->callable())->toEqual($expected)
        ->and(\unserialize(\serialize($serverCallable))->call(['option' => 'y']))->toBe($serverCallable->call(['option' => 'y']));
})->with([
    'Class@method' => [TestServerCallables::class . '@instanceMethod', [TestServerCallables::class, 'instanceMethod']],
    'Class::method' => [TestServerCallables::class . '::staticMethod', [TestServerCallables::class, 'staticMethod']],
    '[object, method]' => [[new TestServerCallables(), 'instanceMethod'], [TestServerCallables::class, 'instanceMethod']],
    'invokable class' => [' ' . TestServerCallables::class . ' ', TestServerCallables::class],
]);

it('rejects closures, which cannot be cached', function () {
    new ServerCallable(fn() => null);
})->throws(\ValueError::class, 'A closure cannot be used here');

it('rejects what is not callable', function (mixed $callable) {
    expect(ServerCallable::tryFrom($callable))->toBeNull()
        ->and(fn() => new ServerCallable($callable))->toThrow(\ValueError::class, 'is not a callable');
})->with([
    'unknown function' => ['no_such_function'],
    'unknown class' => ['No\\Such\\Class@method'],
    'unknown method' => [TestServerCallables::class . '@'],
    'class that is not invokable' => [\stdClass::class],
    'empty string' => [''],
    'malformed array' => [[TestServerCallables::class]],
    'object that is not invokable' => [new \stdClass()],
    'number' => [42],
]);

it('describes itself', function () {
    expect((string) new ServerCallable(TestServerCallables::class . '@instanceMethod'))
        ->toBe(TestServerCallables::class . '::instanceMethod');
});
