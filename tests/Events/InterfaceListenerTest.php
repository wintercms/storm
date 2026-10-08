<?php namespace Events;

use Winter\Storm\Events\Dispatcher;

/**
 * Listeners registered against an interface must fire for every event implementing it.
 *
 * This dispatcher wraps listeners in listen() and keys them by priority, so it cannot reuse
 * Laravel's addInterfaceListeners(), which expects raw listeners stored one level flatter.
 * Doing so hands each priority bucket to makeListener() as if the bucket were itself a
 * listener, and dispatching then throws on the resulting invalid array callable.
 */
class InterfaceListenerTest extends \Winter\Storm\Tests\TestCase
{
    public function testListenerRegisteredOnAnInterfaceReceivesTheImplementingEvent()
    {
        $received = [];

        $dispatcher = new Dispatcher();
        $dispatcher->listen(InterfaceListenerTestContract::class, function ($event) use (&$received) {
            $received[] = get_class($event);
        });

        $dispatcher->dispatch(new InterfaceListenerTestEvent('payload'));

        $this->assertSame([InterfaceListenerTestEvent::class], $received);
    }

    public function testInterfaceAndClassListenersBothFireInPriorityOrder()
    {
        $order = [];

        $dispatcher = new Dispatcher();
        $dispatcher->listen(InterfaceListenerTestEvent::class, function () use (&$order) {
            $order[] = 'class-default';
        });
        $dispatcher->listen(InterfaceListenerTestEvent::class, function () use (&$order) {
            $order[] = 'class-high';
        }, 10);
        $dispatcher->listen(InterfaceListenerTestContract::class, function () use (&$order) {
            $order[] = 'interface';
        });

        $dispatcher->dispatch(new InterfaceListenerTestEvent('payload'));

        // Class listeners sort by descending priority; interface listeners are appended after.
        $this->assertSame(['class-high', 'class-default', 'interface'], $order);
    }

    public function testGetListenersIncludesInterfaceListenersOnce()
    {
        $dispatcher = new Dispatcher();
        $dispatcher->listen(InterfaceListenerTestContract::class, fn () => null);
        $dispatcher->listen(InterfaceListenerTestEvent::class, fn () => null);

        $this->assertCount(2, $dispatcher->getListeners(InterfaceListenerTestEvent::class));
        $this->assertCount(1, $dispatcher->getListeners(InterfaceListenerTestContract::class));
    }

    public function testAnEventWithNoInterfaceListenersStillDispatches()
    {
        $hit = false;

        $dispatcher = new Dispatcher();
        $dispatcher->listen(InterfaceListenerTestEvent::class, function () use (&$hit) {
            $hit = true;
        });

        $dispatcher->dispatch(new InterfaceListenerTestEvent('payload'));

        $this->assertTrue($hit);
    }

    /**
     * Class-string listeners are how Laravel Octane registers its post-operation cleanup against
     * the OperationTerminated contract, so they must resolve through the container as well.
     */
    public function testClassStringListenerRegisteredOnAnInterfaceIsResolved()
    {
        InterfaceListenerTestHandler::$calls = [];

        $dispatcher = new Dispatcher();
        $dispatcher->listen(InterfaceListenerTestContract::class, InterfaceListenerTestHandler::class);

        $dispatcher->dispatch(new InterfaceListenerTestEvent('from-contract'));

        $this->assertSame(['from-contract'], InterfaceListenerTestHandler::$calls);
    }
}

interface InterfaceListenerTestContract
{
}

class InterfaceListenerTestEvent implements InterfaceListenerTestContract
{
    public function __construct(public string $value)
    {
    }
}

class InterfaceListenerTestHandler
{
    public static array $calls = [];

    public function handle(InterfaceListenerTestEvent $event): void
    {
        static::$calls[] = $event->value;
    }
}
