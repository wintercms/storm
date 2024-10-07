<?php

use Illuminate\Http\Request;
use Winter\Storm\Foundation\Http\Middleware\CheckForTrustedProxies;

/**
 * Test cases for testing trusted proxies.
 *
 * Based off the test cases from https://github.com/fideloper/TrustedProxy. Credit to @fideloper for the original
 * implementation.
 */
class CheckForTrustedProxiesTest extends TestCase
{
    /**
     * Test an untrusted connection through a proxy.
     *
     * @return void
     */
    public function testUntrusted()
    {
        $request = $this->createProxiedRequest();

        $this->assertEquals('192.168.10.10', $request->getClientIp());
        $this->assertEquals('http', $request->getScheme());
        $this->assertEquals('root.host', $request->getHost());
        $this->assertEquals(8000, $request->getPort());
    }

    /**
     * Test a trusted connection through a proxy, request modified manually.
     *
     * @return void
     */
    public function testTrustedProxy()
    {
        $request = $this->createProxiedRequest();
        $request->setTrustedProxies(['192.168.10.10'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);

        $this->assertEquals('173.174.200.38', $request->getClientIp());
        $this->assertEquals('https', $request->getScheme());
        $this->assertEquals('proxy.host', $request->getHost());
        $this->assertEquals(443, $request->getPort());
    }

    /**
     * Test a trusted connection through a proxy through middleware with a wildcard proxy setting.
     *
     * @return void
     */
    public function testTrustedProxyMiddlewareWithWildcard()
    {
        $middleware = $this->createTrustedProxyMock('*', 'HEADER_X_FORWARDED_ALL');
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('173.174.200.38', $request->getClientIp());
            $this->assertEquals('https', $request->getScheme());
            $this->assertEquals('proxy.host', $request->getHost());
            $this->assertEquals(443, $request->getPort());
        });
    }

    /**
     * Test a trusted connection through a proxy through middleware with a specific IP address string.
     *
     * @return void
     */
    public function testTrustedProxyMiddlewareWithStringIp()
    {
        $middleware = $this->createTrustedProxyMock('192.168.10.10', 'HEADER_X_FORWARDED_ALL');
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('173.174.200.38', $request->getClientIp());
            $this->assertEquals('https', $request->getScheme());
            $this->assertEquals('proxy.host', $request->getHost());
            $this->assertEquals(443, $request->getPort());
        });

        // Failure state
        $middleware = $this->createTrustedProxyMock('192.168.12.12', 'HEADER_X_FORWARDED_ALL');
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('192.168.10.10', $request->getClientIp());
            $this->assertEquals('http', $request->getScheme());
            $this->assertEquals('root.host', $request->getHost());
            $this->assertEquals(8000, $request->getPort());
        });
    }

    /**
     * Test a trusted connection through a proxy through middleware with a CSV string of IP addresses.
     *
     * @return void
     */
    public function testTrustedProxyMiddlewareWithStringCsv()
    {
        $middleware = $this->createTrustedProxyMock('192.168.10.10, 192.168.11.11', 'HEADER_X_FORWARDED_ALL');
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('173.174.200.38', $request->getClientIp());
            $this->assertEquals('https', $request->getScheme());
            $this->assertEquals('proxy.host', $request->getHost());
            $this->assertEquals(443, $request->getPort());
        });

        // Failure state
        $middleware = $this->createTrustedProxyMock('192.168.12.12, 192.168.13.13', 'HEADER_X_FORWARDED_ALL');
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('192.168.10.10', $request->getClientIp());
            $this->assertEquals('http', $request->getScheme());
            $this->assertEquals('root.host', $request->getHost());
            $this->assertEquals(8000, $request->getPort());
        });
    }

    /**
     * Test a trusted connection through a proxy through middleware with an array of IP addresses.
     *
     * @return void
     */
    public function testTrustedProxyMiddlewareWithArray()
    {
        $middleware = $this->createTrustedProxyMock(['192.168.10.10', '192.168.11.11'], 'HEADER_X_FORWARDED_ALL');
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('173.174.200.38', $request->getClientIp());
            $this->assertEquals('https', $request->getScheme());
            $this->assertEquals('proxy.host', $request->getHost());
            $this->assertEquals(443, $request->getPort());
        });

        // Failure state
        $middleware = $this->createTrustedProxyMock(['192.168.12.12', '192.168.13.13'], 'HEADER_X_FORWARDED_ALL');
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('192.168.10.10', $request->getClientIp());
            $this->assertEquals('http', $request->getScheme());
            $this->assertEquals('root.host', $request->getHost());
            $this->assertEquals(8000, $request->getPort());
        });
    }

    /**
     * Test untrusting most headers
     *
     * @return void
     */
    public function testUntrustedHeaders()
    {
        $middleware = $this->createTrustedProxyMock('192.168.10.10', Request::HEADER_FORWARDED);
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('192.168.10.10', $request->getClientIp());
            $this->assertEquals('http', $request->getScheme());
            $this->assertEquals('root.host', $request->getHost());
            $this->assertEquals(8000, $request->getPort());
        });
    }

    /**
     * Test untrusting most headers, and using only the "X-Forwarder-For" header
     *
     * @return void
     */
    public function testTrustOnlyForwardedFor()
    {
        $middleware = $this->createTrustedProxyMock('192.168.10.10', Request::HEADER_X_FORWARDED_FOR);
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('173.174.200.38', $request->getClientIp());
            $this->assertEquals('http', $request->getScheme());
            $this->assertEquals('root.host', $request->getHost());
            $this->assertEquals(8000, $request->getPort());
        });
    }

    /**
     * Test untrusting most headers, and using only the "X-Forwarder-Port" header
     *
     * @return void
     */
    public function testTrustOnlyForwardedPort()
    {
        $middleware = $this->createTrustedProxyMock('192.168.10.10', Request::HEADER_X_FORWARDED_PORT);
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('192.168.10.10', $request->getClientIp());
            $this->assertEquals('http', $request->getScheme());
            $this->assertEquals('root.host', $request->getHost());
            $this->assertEquals(443, $request->getPort());
        });
    }

    /**
     * Test untrusting most headers, and using only the "X-Forwarder-Proto" header.
     *
     * Also tests a string "header" value.
     *
     * @return void
     */
    public function testTrustOnlyForwardedProto()
    {
        $middleware = $this->createTrustedProxyMock('192.168.10.10', Request::HEADER_X_FORWARDED_PROTO);
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('192.168.10.10', $request->getClientIp());
            $this->assertEquals('https', $request->getScheme());
            $this->assertEquals('root.host', $request->getHost());
            $this->assertEquals(8000, $request->getPort());
        });
    }

    /**
     * Test trusting multiple headers.
     *
     * @return void
     */
    public function testTrustOnlyForwardedHostPortAndProto()
    {
        $middleware = $this->createTrustedProxyMock(
            '192.168.10.10',
            Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO
        );
        $request = $this->createProxiedRequest();

        $middleware->handle($request, function ($request) {
            $this->assertEquals('192.168.10.10', $request->getClientIp());
            $this->assertEquals('https', $request->getScheme());
            $this->assertEquals('proxy.host', $request->getHost());
            $this->assertEquals(443, $request->getPort());
        });
    }

    /**
     * Tests getting client IP from the "X-Forwarded-For" header with multiple IP addresses
     *
     * @return void
     */
    public function testGetClientIps()
    {
        $middleware = $this->createTrustedProxyMock(
            '192.168.10.10',
            Request::HEADER_X_FORWARDED_FOR
        );

        $forwardedFor = [
            '192.0.2.2',
            '192.0.2.2, 192.0.2.199',
            '192.0.2.2, 192.0.2.199, 99.99.99.99',
            '192.0.2.2,192.0.2.199',
        ];

        foreach ($forwardedFor as $forwardedForHeader) {
            $request = $this->createProxiedRequest(['HTTP_X_FORWARDED_FOR' => $forwardedForHeader]);

            $middleware->handle($request, function ($request) {
                $ips = $request->getClientIps();
                $this->assertEquals('192.0.2.2', end($ips));
            });
        }
    }

    /**
     * Tests getting client IP from the "X-Forwarded-For" header with multiple IP addresses where some
     * proxies are trusted.
     *
     * @return void
     */
    public function testGetClientIpSomeProxiesTrusted()
    {
        $middleware = $this->createTrustedProxyMock(
            ['192.168.10.10', '192.0.2.199'],
            Request::HEADER_X_FORWARDED_FOR
        );

        $forwardedFor = [
            '192.0.2.2',
            '192.0.2.2, 192.0.2.199',
            '99.99.99.99, 192.0.2.2, 192.0.2.199',
            '192.0.2.2,192.0.2.199',
        ];

        foreach ($forwardedFor as $forwardedForHeader) {
            $request = $this->createProxiedRequest(['HTTP_X_FORWARDED_FOR' => $forwardedForHeader]);

            $middleware->handle($request, function ($request) {
                $this->assertEquals('192.0.2.2', $request->getClientIp());
            });
        }
    }

    /**
     * Tests getting client IP from the "X-Forwarded-For" header with multiple IP addresses where all
     * proxies are trusted through the wildcard character.
     *
     * @return void
     */
    public function testGetClientIpAllProxiesTrusted()
    {
        $middleware = $this->createTrustedProxyMock(
            '*',
            Request::HEADER_X_FORWARDED_FOR
        );

        $forwardedFor = [
            '192.0.2.2',
            '192.0.2.199, 192.0.2.2',
            '192.0.2.199,192.0.2.2',
            '99.99.99.99,192.0.2.199,192.0.2.2',
        ];

        foreach ($forwardedFor as $forwardedForHeader) {
            $request = $this->createProxiedRequest(['HTTP_X_FORWARDED_FOR' => $forwardedForHeader]);

            $middleware->handle($request, function ($request) {
                $this->assertEquals('192.0.2.2', $request->getClientIp());
            });
        }
    }

    /**
     * Tests getting client IP from the "X-Forwarded-For" header with multiple IP addresses where all
     * proxies in the chain are trusted through the double-wildcard ('**') characters.
     *
     * @return void
     */
    public function testGetClientIpAllChainedProxiesTrusted()
    {
        $middleware = $this->createTrustedProxyMock(
            '**',
            Request::HEADER_X_FORWARDED_FOR
        );

        $forwardedFor = [
            '192.0.2.2',
            '192.0.2.2, 192.0.2.199',
            '192.0.2.2, 99.99.99.99, 192.0.2.199',
            '192.0.2.2, 2001:0db8:0a0b:12f0:0000:0000:0000:0001, 192.0.2.199',
            '192.0.2.2, 2c01:0db8:0a0b:12f0:0000:0000:0000:0001, 192.0.2.199',
            '192.0.2.2,192.0.2.199',
        ];

        foreach ($forwardedFor as $forwardedForHeader) {
            $request = $this->createProxiedRequest(['HTTP_X_FORWARDED_FOR' => $forwardedForHeader]);

            $middleware->handle($request, function ($request) {
                $this->assertEquals('192.0.2.2', $request->getClientIp());
            });
        }

        // Failure state
        $middleware = $this->createTrustedProxyMock(
            '*',
            Request::HEADER_X_FORWARDED_FOR
        );

        $forwardedFor = [
            '192.0.2.2',
            '192.0.2.2, 192.0.2.199',
            '192.0.2.2, 99.99.99.99, 192.0.2.199',
            '192.0.2.2, 2001:0db8:0a0b:12f0:0000:0000:0000:0001, 192.0.2.199',
            '192.0.2.2, 2c01:0db8:0a0b:12f0:0000:0000:0000:0001, 192.0.2.199',
            '192.0.2.2,192.0.2.199',
        ];

        // Items 1 should pass. The rest should fail.
        foreach ($forwardedFor as $i => $forwardedForHeader) {
            $request = $this->createProxiedRequest(['HTTP_X_FORWARDED_FOR' => $forwardedForHeader]);

            $middleware->handle($request, function ($request) use ($i) {
                if ($i === 0) {
                    $this->assertEquals('192.0.2.2', $request->getClientIp(), 'Index ' . $i . ' failed when it should pass');
                } else {
                    $this->assertNotEquals('192.0.2.2', $request->getClientIp(), 'Index ' . $i . ' passed when it should fail');
                }
            });
        }
    }

    /**
     * Create a proxied request for testing.
     *
     * @param array $overrides
     * @return Request
     */
    protected function createProxiedRequest(array $overrides = [])
    {
        $defaults = [
            'HTTP_X_FORWARDED_FOR' => '173.174.200.38',
            'HTTP_X_FORWARDED_HOST' => 'proxy.host',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'SERVER_PORT' => 8000,
            'HTTP_HOST' => 'root.host',
            'REMOTE_ADDR' => '192.168.10.10',
        ];

        $request = Request::create(
            'http://root.host:8000/proxy',
            'GET',
            [],
            [],
            [],
            array_replace($defaults, $overrides)
        );

        // Reset trusted proxies and headers
        $request->setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);

        return $request;
    }

    /**
     * Create a mock of the middleware for testing.
     *
     * @param array|string $trustedProxies
     * @param integer|string $trustedHeaders
     * @return CheckForTrustedProxies
     */
    protected function createTrustedProxyMock($trustedProxies = [], $trustedHeaders = -1)
    {
        $middleware = $this->getMockBuilder(CheckForTrustedProxies::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['proxies', 'headers'])
            ->getMock();

        $middleware->expects($this->any())
            ->method('proxies')
            ->willReturn($trustedProxies);

        $middleware->expects($this->any())
            ->method('headers')
            ->willReturn($trustedHeaders);

        return $middleware;
    }
}
