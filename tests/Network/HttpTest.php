<?php

use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Network\Http;

class HttpTest extends TestCase
{
    use \DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

    /**
     * Http object fixture
     *
     * @var \Winter\Storm\Network\Http
     */
    protected $Http;

    public function setUp(): void
    {
        $this->Http = new Http;
    }

    public function testSetOptionsViaConstants()
    {
        $this->Http->setOption(CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        $this->Http->setOption(CURLOPT_PIPEWAIT, false);
        $this->Http->setOption(CURLOPT_VERBOSE, true);

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetOptionsViaStrings()
    {
        $this->Http->setOption('CURLOPT_DNS_USE_GLOBAL_CACHE', true);
        $this->Http->setOption('CURLOPT_PIPEWAIT', false);
        $this->Http->setOption('CURLOPT_VERBOSE', true);

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetOptionsViaIntegers()
    {
        $this->Http->setOption(91, true); //CURLOPT_DNS_USE_GLOBAL_CACHE
        $this->Http->setOption(237, false); //CURLOPT_PIPEWAIT
        $this->Http->setOption(41, true); //CURLOPT_VERBOSE

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetInvalidOptionViaString()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $this->Http->setOption('CURLOPT_SOME_RANDOM_CONSTANT', true);
    }

    public function testSetInvalidOptionViaInteger()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $this->Http->setOption(99999, true);
    }

    public function testSetOptionsViaArrayOfConstants()
    {
        $this->Http->setOption([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ]);

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetOptionsViaArrayOfIntegers()
    {
        $this->Http->setOption([
            91 => true, //CURLOPT_DNS_USE_GLOBAL_CACHE
            237 => false, //CURLOPT_PIPEWAIT
            41 => true //CURLOPT_VERBOSE
        ]);

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetOptionsViaArrayOfStrings()
    {
        $this->Http->setOption([
            'CURLOPT_DNS_USE_GLOBAL_CACHE' => true,
            'CURLOPT_PIPEWAIT' => false,
            'CURLOPT_VERBOSE' => true
        ]);

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetInvalidOptionViaArrayOfStrings()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $this->Http->setOption([
            'CURLOPT_DNS_USE_GLOBAL_CACHE' => true,
            'CURLOPT_PIPEWAIT' => false,
            'CURLOPT_VERBOSE' => true,
            'CURLOPT_SOME_RANDOM_CONSTANT' => true
        ]);
    }

    public function testSetInvalidOptionViaArrayOfIntegers()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $this->Http->setOption([
            91 => true, //CURLOPT_DNS_USE_GLOBAL_CACHE
            237 => false, //CURLOPT_PIPEWAIT
            41 => true, //CURLOPT_VERBOSE
            99999 => true // Invalid CURLOPT integer
        ]);
    }

    public function testSetRequestData()
    {
        $this->Http->data('foo', 'bar');
        $this->assertEquals('foo=bar', $this->Http->getRequestData());
    }

    public function testSetRequestDataArray()
    {
        $this->Http->data([
            'foo' => 'bar',
            'bar' => 'foo'
        ]);
        $this->assertEquals('foo=bar&bar=foo', $this->Http->getRequestData());
    }

    public function testSetPostFields()
    {
        $this->Http->setOption(CURLOPT_POSTFIELDS, 'foobar');
        $this->assertEquals('foobar', $this->Http->getRequestData());
    }

    public function testRequestDataOverridePostFields()
    {
        $this->Http->data([
            'foo' => 'bar',
            'bar' => 'foo'
        ]);
        $this->Http->setOption(CURLOPT_POSTFIELDS, 'foobar');
        $this->assertEquals('foo=bar&bar=foo', $this->Http->getRequestData());
    }

    public function testGetApiPingEndpoint()
    {
        $http = $this->Http->get('https://api.wintercms.com/marketplace/ping');

        // Ensure Http class has necessary request config
        $this->assertInstanceOf(Http::class, $http);
        $this->assertEquals('GET', $http->method);
        $this->assertEquals('https://api.wintercms.com/marketplace/ping', $http->url);
        $this->assertEquals('pong', $http->body);

        // Check some headers
        $this->assertIsArray($http->headers);
        $this->assertNotEmpty($http->headers);
        $this->assertEquals('200', $http->headers['HTTP/2']);
        $this->assertStringContainsString('text/html', $http->headers['content-type']);
    }

    public function testGetApiPingEndpointToFile()
    {
        // Temp file for test case
        $tmpFile = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . '.response.file';

        $http = $this->Http->get('https://api.wintercms.com/marketplace/ping', function ($http) use ($tmpFile) {
            $http->toFile($tmpFile);
        });

        // Ensure Http class has necessary request config
        $this->assertInstanceOf(Http::class, $http);
        $this->assertEquals('GET', $http->method);
        $this->assertEquals('https://api.wintercms.com/marketplace/ping', $http->url);

        // Ensure body is empty in the Http instance, but has been written to the file
        $this->assertEmpty($http->body);
        $this->assertFileExists($tmpFile);
        $this->assertEquals('pong', file_get_contents($tmpFile));

        // Delete temp file
        @unlink($tmpFile);

        // Check some headers (headers should still exist in the Http instance)
        $this->assertIsArray($http->headers);
        $this->assertNotEmpty($http->headers);
        $this->assertEquals('200', $http->headers['HTTP/2']);
        $this->assertStringContainsString('text/html', $http->headers['content-type']);
    }
}
