<?php

use Illuminate\Http\Request;
use Winter\Storm\Foundation\Http\Kernel;

class KernelTest extends \Winter\Storm\Tests\TestCase
{
    public function testDeferredCallbacksRunAfterTheResponse()
    {
        $ran = false;

        $this->app['router']->get('/deferred', function () use (&$ran) {
            defer(function () use (&$ran) {
                $ran = true;
            });

            return 'OK';
        });

        $kernel = new Kernel($this->app, $this->app['router']);
        $request = Request::create('/deferred');
        $response = $kernel->handle($request);

        $this->assertSame('OK', $response->getContent());
        $this->assertFalse($ran);

        $kernel->terminate($request, $response);

        $this->assertTrue($ran);
    }
}
