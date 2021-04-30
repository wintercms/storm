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
    
    /**
     * Test wether the dispatcher accepts a QueuedClosure
     * TODO: Figure out how to test successful execution of event closure on a triggered event.
     */
    public function testQueuedClosureListen()
    {
        $dispatcher = new Dispatcher();
        $dispatcher->listen(new QueuedClosure(function (EventTest $event) use (&$magic_value) {
        }));
        $this->addToAssertionCount(1);
    }
}
