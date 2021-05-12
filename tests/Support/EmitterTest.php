<?php

use Illuminate\Events\QueuedClosure;

class EmitterTest extends TestCase
{
    /**
     * The object under test.
     *
     * @var object
     */
    private $traitObject;

    /**
     * Sets up the fixture.
     *
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp(): void
    {
        $traitName = 'Winter\Storm\Support\Traits\Emitter';
        $this->traitObject = $this->getObjectForTrait($traitName);
    }

    //
    // Tests
    //

    public function testBind()
    {
        $emitter = $this->traitObject;
        $result = false;

        $emitter->fireEvent('event.test');
        $this->assertEquals(false, $result);

        $emitter->bindEvent('event.test', function () use (&$result) {
            $result = true;
        });

        $emitter->fireEvent('event.test');
        $this->assertEquals(true, $result);
    }

    public function testBindOnce()
    {
        $emitter = $this->traitObject;
        $result = 1;

        $callback = function () use (&$result) {
            $result++;
        };

        $emitter->bindEventOnce('event.test', $callback);
        $emitter->fireEvent('event.test');
        $emitter->fireEvent('event.test');
        $emitter->fireEvent('event.test');

        $this->assertEquals(2, $result);
    }

    public function testUnbindEvent()
    {
        $emitter = $this->traitObject;
        $result = false;

        $callback = function () use (&$result) {
            $result = true;
        };

        $emitter->bindEvent('event.test', $callback);
        $emitter->unbindEvent('event.test');
        $emitter->fireEvent('event.test');

        $this->assertEquals(false, $result);
    }

    public function testFireEvent()
    {
        $emitter = $this->traitObject;
        $count = 0;

        $callback = function () use (&$count) {
            $count++;
        };

        $emitter->bindEvent('event.test', $callback);
        $emitter->bindEvent('event.test', $callback);
        $emitter->bindEvent('event.test', $callback);
        $emitter->fireEvent('event.test');

        $this->assertEquals(3, $count);
    }

    public function testFireEventResult()
    {
        $emitter = $this->traitObject;
        $result = $emitter->fireEvent('event.test');
        $this->assertEmpty($result);

        $emitter->bindEvent('event.test', function () {
            return 'foo';
        });
        $result = $emitter->fireEvent('event.test');
        $this->assertNotNull($result);
    }

    public function testBindPriority()
    {
        $emitter = $this->traitObject;
        $result = '';

        // Skip code smell checks for this block of code.
        // phpcs:disable
        $emitter->bindEvent('event.test', function () use (&$result) { $result .= 'the '; }, 90);
        $emitter->bindEvent('event.test', function () use (&$result) { $result .= 'quick '; }, 80);
        $emitter->bindEvent('event.test', function () use (&$result) { $result .= 'brown '; }, 70);
        $emitter->bindEvent('event.test', function () use (&$result) { $result .= 'fox '; }, 60);
        $emitter->bindEvent('event.test', function () use (&$result) { $result .= 'jumped '; }, 50);
        $emitter->bindEvent('event.test', function () use (&$result) { $result .= 'over '; }, 40);
        $emitter->bindEvent('event.test', function () use (&$result) { $result .= 'the '; }, 30);
        $emitter->bindEvent('event.test', function () use (&$result) { $result .= 'lazy '; }, 20);
        $emitter->bindEvent('event.test', function () use (&$result) { $result .= 'dog'; }, 10);
        $emitter->fireEvent('event.test');
        // phpcs:enable

        $this->assertEquals('the quick brown fox jumped over the lazy dog', $result);
    }

    /**
     * Test closure usage
     */
    public function testTypedClosureListen()
    {
        $magic_value = false;
        $dispatcher = $this->traitObject;
        $dispatcher->bindEvent(function (EventTest $event) use (&$magic_value) {
            $magic_value = true;
        });
        $dispatcher->fireEvent('test.test');
        $this->assertFalse($magic_value);
        $dispatcher->fireEvent(new EventTest);
        $this->assertTrue($magic_value);
    }

    public function testClosurePriorities()
    {
        $magic_value = 0;
        $dispatcher = $this->traitObject;

        $dispatcher->bindEvent(function (EventTest $test) use (&$magic_value) {
            $magic_value = 42;
        }, 1);
        $dispatcher->bindEvent(function (EventTest $test) use (&$magic_value) {
            $magic_value = 1;
        }, 2);

        $dispatcher->fireEvent(new EventTest());
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
        $dispatcher = $this->traitObject;
        $magic_value = 0;

        // Test natural sorting without priority to the queued tasks to be queued.
        $dispatcher->bindEvent($mock_queued_closure_should_not_match);
        $dispatcher->bindEvent($mock_queued_closure_should_match);
        $dispatcher->fireEvent(new EventTest());
        $this->assertEquals(42, $magic_value);

        // Test priority sorting for the queued tasks to be queued
        $magic_value = 0;
        $dispatcher->bindEvent($mock_queued_closure_should_match, 1);
        $dispatcher->bindEvent($mock_queued_closure_should_not_match, 2);
        $dispatcher->fireEvent(new EventTest());
        $this->assertEquals(42, $magic_value);
    }

    /**
     * Test whether the Emitter accepts a QueuedClosure
     */
    public function testQueuedClosureListen()
    {
        $magic_value = false;
        $mock_queued_closure = $this->createMock(QueuedClosure::class);
        $mock_queued_closure->closure = function (EventTest $test) use (&$magic_value) {
            $magic_value = true;
        };
        $mock_queued_closure->method('resolve')->willReturn($mock_queued_closure->closure);
        $dispatcher = $this->traitObject;
        $dispatcher->bindEvent($mock_queued_closure);
        $dispatcher->fireEvent(new EventTest());
        $this->assertTrue($magic_value);
    }

    public function testClosureSerialisation()
    {
        $emitter = new EmitterClass();
        $test = 'foobar';
        $emitter->bindEvent($test, function () use ($test) {
            EmitterClass::$output = $test;
        });
        $emitter->bindEvent(function (EventTest $event) use ($test) {
            EmitterClass::$output = $test.$test;
        });
        $serialized = serialize($emitter);
        $unserialized = unserialize($serialized);
        $unserialized->fireEvent($test);
        $this->assertEquals($test, EmitterClass::$output);

        $unserialized->fireEvent(new EventTest());
        $this->assertEquals($test.$test, EmitterClass::$output);
    }

    public function testNestedClosureSerialisation()
    {
        $emitter = new EmitterClass();
        $test = 'foobar';
        $emitter->bindEvent($test, function () use ($test) {
            EmitterClass::$output = function () use ($test) {
                return $test;
            };
        });

        $serialized = serialize($emitter);
        $unserialized = unserialize($serialized);
        $unserialized->fireEvent($test);

        $closure = EmitterClass::$output;
        $this->assertInstanceOf(Closure::class, $closure);
        $this->assertEquals($test, $closure());
    }
}
class EmitterClass
{
    use \Winter\Storm\Support\Traits\Emitter;

    /**
     * @var string $output used for keeping a testable variable as references don't survive serialisation
     */
    public static $output;
}
