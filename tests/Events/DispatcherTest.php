<?php

use Winter\Storm\Foundation\Application;
use Winter\Storm\Events\Dispatcher;
use Illuminate\Events\QueuedClosure;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Queue\QueueServiceProvider;
use Winter\Storm\Config\ConfigServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Bus;

class DispatcherTest extends TestCase
{

    public function setUp(): void
    {
        include_once __DIR__.'/../fixtures/events/EventTest.php';

        parent::setUp();
    }

    /**
     * Test normal string event dispatch
     */
    public function testNormalListen()
    {
        /**
         * Test normal usage
         */
        $magic_value = false;
        $dispatcher = new Dispatcher();
        $dispatcher->listen('test.test', function () use (&$magic_value) {
            $magic_value = true;
        });
        $dispatcher->fire('test.test');
        $this->assertTrue($magic_value);
    }

    /**
     * Test closure usage
     */
    public function testTypedClosureListen()
    {
        $magic_value = false;
        $dispatcher = new Dispatcher();
        $dispatcher->listen(function (EventTest $event) use (&$magic_value) {
            $magic_value = true;
        });
        $dispatcher->dispatch('test.test');
        $this->assertFalse($magic_value);
        $dispatcher->dispatch(new EventTest);
        $this->assertTrue($magic_value);
    }

    public function testClosureWithValueArgument()
    {
        $original = false;

        $dispatcher = new Dispatcher();
        $dispatcher->listen('test', function ($value) {
            $value = true;
        });
        $dispatcher->dispatch('test', [$original]);

        $this->assertFalse($original);
    }

    public function testClosureWithReferenceArgument()
    {
        $original = false;

        $dispatcher = new Dispatcher();
        $dispatcher->listen('test', function (&$value) {
            $value = true;
        });
        $dispatcher->dispatch('test', [&$original]);

        $this->assertTrue($original);
    }

    public function testStringEventPriorities()
    {
        $magic_value = 0;
        $dispatcher = new Dispatcher();

        $dispatcher->listen("test.test", function () use (&$magic_value) {
            $magic_value = 42;
        }, 1);
        $dispatcher->listen("test.test", function () use (&$magic_value) {
            $magic_value = 1;
        }, 2);

        $dispatcher->dispatch("test.test");
        $this->assertEquals(42, $magic_value);
    }

    public function testClosurePriorities()
    {
        $magic_value = 0;
        $dispatcher = new Dispatcher();

        $dispatcher->listen(function (EventTest $test) use (&$magic_value) {
            $magic_value = 42;
        }, 1);
        $dispatcher->listen(function (EventTest $test) use (&$magic_value) {
            $magic_value = 1;
        }, 2);

        $dispatcher->dispatch(new EventTest());
        $this->assertEquals(42, $magic_value);
    }

    public function testQueuedClosurePriorities()
    {
        $mock_queued_closure_should_match = $this->createMock(QueuedClosure::class);
        $mock_queued_closure_should_match->closure = function (EventTest $test) use (&$magic_value) {
            $magic_value = 42;
        };
        $mock_queued_closure_should_match->method('resolve')->willReturn($mock_queued_closure_should_match->closure);

        $mock_queued_closure_should_not_match = $this->createMock(QueuedClosure::class);
        $mock_queued_closure_should_not_match->closure = function (EventTest $test) use (&$magic_value) {
            $magic_value = 2;
        };
        $mock_queued_closure_should_not_match->method('resolve')->willReturn($mock_queued_closure_should_not_match->closure);
        $dispatcher = new Dispatcher();
        $magic_value = 0;

        // Test natural sorting without priority to the queued tasks to be queued.
        $dispatcher->listen($mock_queued_closure_should_not_match);
        $dispatcher->listen($mock_queued_closure_should_match);
        $dispatcher->dispatch(new EventTest());
        $this->assertEquals(42, $magic_value);

        // Test priority sorting for the queued tasks to be queued
        $magic_value = 0;
        $dispatcher->listen($mock_queued_closure_should_match, 1);
        $dispatcher->listen($mock_queued_closure_should_not_match, 2);
        $dispatcher->dispatch(new EventTest());
        $this->assertEquals(42, $magic_value);
    }

    /**
     * Test whether the dispatcher accepts a QueuedClosure
     */
    public function testQueuedClosureListen()
    {
        $magic_value = false;
        $mock_queued_closure = $this->createMock(QueuedClosure::class);
        $mock_queued_closure->closure = function (EventTest $test) use (&$magic_value) {
            $magic_value = true;
        };
        $mock_queued_closure->method('resolve')->willReturn($mock_queued_closure->closure);
        $dispatcher = new Dispatcher();
        $dispatcher->listen($mock_queued_closure);
        $dispatcher->dispatch(new EventTest());
        $this->assertTrue($magic_value);
    }
}
