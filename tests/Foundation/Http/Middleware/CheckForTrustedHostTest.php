<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Winter\Storm\Router\UrlGenerator;
use Winter\Storm\Foundation\Http\Middleware\CheckForTrustedHost;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

/**
 * Adaptation of https://github.com/laravel/framework/pull/27206. Credit to @shrft for original implentation.
 */
class CheckForTrustedHostTest extends TestCase
{
    protected static $orignalTrustHosts;

    public static function setUpBeforeClass(): void
    {
        self::$orignalTrustHosts = Request::getTrustedHosts();
    }

    public static function tearDownAfterClass(): void
    {
        Request::setTrustedHosts(self::$orignalTrustHosts);
    }

    public function testTrustedHost()
    {
        $trustedHosts = ['wintercms.com'];
        $headers = ['HOST' => 'wintercms.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://wintercms.com/', $url);
    }

    public function testTrustedHostWwwSubdomain()
    {
        $trustedHosts = ['www.wintercms.com'];
        $headers = ['HOST' => 'www.wintercms.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://www.wintercms.com/', $url);
    }

    public function testTrustedHostWwwSubdomainFailure()
    {
        $this->expectException(SuspiciousOperationException::class);

        $trustedHosts = ['wintercms.com'];
        $headers = ['HOST' => 'www.wintercms.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $urlGenerator->to('/');
    }

    public function testTrustedHostWwwRegex()
    {
        $trustedHosts = ['^(www\.)?wintercms\.com$'];
        $headers = ['HOST' => 'wintercms.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://wintercms.com/', $url);

        $headers = ['HOST' => 'www.wintercms.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://www.wintercms.com/', $url);
    }

    public function testTrustedIpHost()
    {
        $trustedHosts = ['127.0.0.1'];
        $headers = ['HOST' => '127.0.0.1'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://127.0.0.1/', $url);
    }

    public function testNoTrustedHostsSet()
    {
        $trustedHosts = false;
        $headers = ['HOST' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://malicious.com/', $url);
    }

    public function testThrowExceptionForUntrustedHosts()
    {
        $this->expectException(SuspiciousOperationException::class);

        $trustedHosts = ['wintercms.com'];
        $headers = ['HOST' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $urlGenerator->to('/');
    }

    public function testThrowExceptionForUntrustedServerName()
    {
        $this->expectException(SuspiciousOperationException::class);

        $trustedHosts = ['wintercms.com'];
        $headers = [];
        $servers = ['SERVER_NAME' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $urlGenerator->to('/');
    }

    public function testThrowExceptionForUntrustedServerAddr()
    {
        $this->expectException(SuspiciousOperationException::class);

        $trustedHosts = ['wintercms.com'];
        $headers = [];
        $servers = ['SERVER_ADDR' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $urlGenerator->to('/');
    }

    public function testRegexTrustedHost()
    {
        $trustedHosts = ['^[a-z0-9]+\.wintercms\.com$'];
        $headers = ['HOST' => 'test123.wintercms.com'];
        $servers = [];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://test123.wintercms.com/', $url);

        $headers = ['HOST' => 'test456.wintercms.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://test456.wintercms.com/', $url);
    }

    public function testRegexFailTrustedHost()
    {
        $this->expectException(SuspiciousOperationException::class);

        $trustedHosts = ['^[a-z0-9]+\.wintercms\.com$'];
        $headers = ['HOST' => 'test.123.wintercms.com'];
        $servers = [];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $urlGenerator->to('/');
    }

    public function testArrayTrustedHost()
    {
        $trustedHosts = ['test1.wintercms.com', 'test2.wintercms.com'];
        $headers = ['HOST' => 'test1.wintercms.com'];
        $servers = [];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://test1.wintercms.com/', $url);

        $headers = ['HOST' => 'test2.wintercms.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://test2.wintercms.com/', $url);
    }

    public function testArrayFailTrustedHost()
    {
        $this->expectException(SuspiciousOperationException::class);

        $trustedHosts = ['test1.wintercms.com', 'test2.wintercms.com'];
        $headers = ['HOST' => 'test3.wintercms.com'];
        $servers = [];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $urlGenerator->to('/');
    }

    protected function createUrlGenerator($trustedHosts = [], $headers = [], $servers = [])
    {
        $middleware = $this->getMockBuilder(CheckForTrustedHost::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hosts', 'shouldSpecifyTrustedHosts'])
            ->getMock();

        $middleware->expects($this->any())
            ->method('hosts')
            ->willReturn(CheckForTrustedHost::processTrustedHosts($trustedHosts));

        $middleware->expects($this->any())
            ->method('shouldSpecifyTrustedHosts')
            ->willReturn(true);

        $request = new Request;

        foreach ($headers as $key => $val) {
            $request->headers->set($key, $val);
        }

        foreach ($servers as $key => $val) {
            $request->server->set($key, $val);
        }

        $middleware->handle($request, function () {
        });

        $routes = new RouteCollection;
        $routes->add(new Route('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        return new UrlGenerator($routes, $request);
    }
}
