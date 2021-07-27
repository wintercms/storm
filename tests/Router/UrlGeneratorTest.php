<?php

use Illuminate\Http\Request;
use \Winter\Storm\Router\UrlGenerator;
use Illuminate\Routing\RouteCollection;

class UrlGeneratorTest extends TestCase
{
    /**
     * Tests the provided input against both UrlGenerator::buildUrl() and the http_build_url() method
     */
    protected function testBuiltUrl(string $expected, array $config)
    {
        $staticNewUrl = [];
        $globalNewUrl = [];
        $staticGenerated = UrlGenerator::buildUrl($config['url'], $config['replace'] ?? [], $config['flags'] ?? HTTP_URL_REPLACE, $staticNewUrl);
        $globalGenerated = http_build_url($config['url'], $config['replace'] ?? [], $config['flags'] ?? HTTP_URL_REPLACE, $globalNewUrl);

        $this->assertEquals($expected, $staticGenerated);
        $this->assertEquals($expected, $globalGenerated);

        if (!empty($config['newUrl'])) {
            $this->assertEquals($config['newUrl'], $staticNewUrl);
            $this->assertEquals($config['newUrl'], $globalNewUrl);
        }
    }

    /**
     * Tests compliance with RFC 3986 5.2.4, Remove Dot Segements
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3986#section-5.2.4
     * @return void
     */
    public function testRemoveDotSequences()
    {
        $this->assertEquals('https://example.com/a/g', UrlGenerator::buildUrl(
            'https://example.com/',
            '/a/b/c/./../../g',
        ));
    }

    /**
     * Tests compliance with the original PECL http_build_url() function
     *
     * @see https://github.com/ivantcholakov/http_build_url/blob/master/http_build_url_test.php
     * @return void
     */
    public function testComplianceWithPecl()
    {
        $generator = new UrlGenerator(
            new RouteCollection,
            Request::create('https://www.example.com/path/?query=arg#fragment')
        );
        // dd($generator->to(''));

        $urlsToTest = [
            // Complex example from original http_build_url() docs
            // @see https://php.uz/manual/en/function.http-build-url.php
            'ftp://ftp.example.com/pub/files/current/?a=c' => [
                'url'     => 'http://user@www.example.com/pub/index.php?a=b#files',
                'replace' => [
                    'scheme' => 'ftp',
                    'host' => 'ftp.example.com',
                    'path' => 'files/current/',
                    'query' => 'a=c'
                ],
                'flags'   => HTTP_URL_STRIP_AUTH|HTTP_URL_JOIN_PATH|HTTP_URL_JOIN_QUERY|HTTP_URL_STRIP_FRAGMENT,
            ],

            // Removal of single dot segment, strip auth, join paths
            'http://www.example.com/foo/baz' => [
                'url'     => 'http://mike@www.example.com/foo/bar',
                'replace' => './baz',
                'flags'   => HTTP_URL_STRIP_AUTH|HTTP_URL_JOIN_PATH,
            ],

            // Removal of double dot segment, strip user, join paths
            'http://www.example.com/foo/baz' => [
                'url'     => 'http://mike@www.example.com/foo/bar/',
                'replace' => '../baz',
                'flags'   => HTTP_URL_STRIP_USER|HTTP_URL_JOIN_PATH,
            ],

            // Removal of multiple dot segments, strip pass, join paths
            'http://mike@www.example.com/foo/baz' => [
                'url'     => 'http://mike:1234@www.example.com/foo/bar/',
                'replace' => './../baz',
                'flags'   => HTTP_URL_STRIP_PASS|HTTP_URL_JOIN_PATH,
            ],

            // Join query, strip port, path, & fragment
            'http://www.example.com/?a%5B0%5D=1&a%5B1%5D=b&b=c' => [
                'url'     => 'http://www.example.com:8080/foo?a[0]=b#frag',
                'replace' => '?a[0]=1&b=c&a[1]=b',
                'flags'   => HTTP_URL_JOIN_QUERY|HTTP_URL_STRIP_PORT|HTTP_URL_STRIP_FRAGMENT|HTTP_URL_STRIP_PATH,
            ],

            // No scheme or host provided, use current
            // 'https://www.example.com/path/?query#anchor' => [
            //     'url'     => '/path/?query#anchor',
            // ],

            // No host provided, use current
            // 'ftp://www.example.com/path/?query#anchor' => [
            //     'url'     => '/path/?query#anchor',
            //     'replace' => ['scheme' => 'ftp'],
            // ],

            // No host or scheme in original, present in replacement
            'https://ssl.example.com/path/?query#anchor' => [
                'url'     => '/path/?query#anchor',
                'replace' => [
                    'scheme' => 'https',
                    'host' => 'ssl.example.com',
                ],
            ],

            // Default ports for the selected scheme are stripped
            'ftp://ftp.example.com/path/?query#anchor' => [
                'url'     => '/path/?query#anchor',
                'replace' => [
                    'scheme' => 'ftp',
                    'host' => 'ftp.example.com',
                    'port' => 21,
                ],
            ],

            // URL & Replace provided as arrays
            'https://www.example.com:9999/replaced?q=1#n' => [
                'url'     => parse_url('http://example.org/orig?q=1#f'),
                'replace' => parse_url('https://www.example.com:9999/replaced#n'),
            ],

            // Test newUrl parameter
            'https://www.example.com:999/replaced?q=1#n' => [
                'url'     => ('http://example.org/orig?q=1#f'),
                'replace' => ('https://www.example.com:999/replaced#n'),
                'flags'   => 0,
                'newUrl'  => [
                    'scheme'   => 'https',
                    'host'     => 'www.example.com',
                    'port'     => '999',
                    'path'     => '/replaced',
                    'query'    => 'q=1',
                    'fragment' => 'n',
                ],
            ],

            // No scheme or host provided, relative path, use current scheme, host, & path
            // 'https://www.example.com/path/page' => [
            //     'url'     => 'page',
            // ],

            // No scheme or host provided, relative path with multiple segments, use current scheme, host, & path
            // 'https://www.example.com/path/with/some/page' => [
            //     'url'     => 'with/some/page',
            // ],

            // Join relative path with multiple dot segments
            'http://www.example.com/another/location' => [
                'url'     => 'http://www.example.com/path/to/page/',
                'replace' => '../../../another/location',
                'flags'   => HTTP_URL_JOIN_PATH,
            ],

            // Join relative path with multiple dot segments ending with slash
            'http://www.example.com/another/location/' => [
                'url'     => 'http://www.example.com/path/to/page/',
                'replace' => '../../../another/location/',
                'flags'   => HTTP_URL_JOIN_PATH,
            ],

            // Join relative path with multiple dot segments
            'http://www.example.com/path/to/page' => [
                'url'     => 'http://www.example.com/another/location',
                'replace' => '../../path/to/page',
                'flags'   => HTTP_URL_JOIN_PATH,
            ],

            // Join relative path with a dot segment
            'http://www.example.com/path/to/another/location/' => [
                'url'     => 'http://www.example.com/path/to/page/',
                'replace' => '../another/location/',
                'flags'   => HTTP_URL_JOIN_PATH,
            ],

            // Join relative path with a single dot segment
            'http://www.example.com/path/to/page/another/subpage/' => [
                'url'     => 'http://www.example.com/path/to/page/',
                'replace' => './another/subpage/',
                'flags'   => HTTP_URL_JOIN_PATH,
            ],

            // Join absolute path overriding existing path
            'http://www.example.com/another/location/second' => [
                'url'     => 'http://www.example.com/path/to/page/',
                'replace' => '/another/location/second',
                'flags'   => HTTP_URL_JOIN_PATH,
            ],

            // Resolve dot segments in original URL with no replacing values
            'http://www.example.com/another/location/no-replace/' => [
                'url'     => 'http://www.example.com/path/to/page/../../../another/location/no-replace/',
                'replace' => null,
                'flags'   => HTTP_URL_JOIN_PATH,
            ],

            // Replaces query vars @NOTE: may not have worked in PECL implementation, but makes logical sense
            'http://user:pass@www.example.com:8080/pub/index.php?foo=bar#files' => [
                'url'     => 'http://user:pass@www.example.com:8080/pub/index.php?a=b#files',
                'replace' => [
                    'query' => ['foo' => 'bar'],
                ],
            ],

            // Joins query vars
            'http://user:pass@www.example.com:8080/pub/index.php?a=b&foo=bar#files' => [
                'url'     => 'http://user:pass@www.example.com:8080/pub/index.php?a=b#files',
                'replace' => [
                    'query' => ['foo' => 'bar'],
                ],
                'flags'   => HTTP_URL_JOIN_QUERY,
            ],
        ];

        foreach ($urlsToTest as $expected => $config) {
            $this->testBuiltUrl($expected, $config);
        }
    }

    public function testSimpleUrl()
    {
        /**
         * @TODO: Tests to add
         * - proper support for PHP html arrays in query strings
         * - HTML injection attempts
         */
        // dd(http_build_url('https://example.com/testpage/?test="><img src="a" onerror="alert(1)"/>', null, HTTP_URL_JOIN_QUERY));

        $this->testBuiltUrl('https://wintercms.com/', [
            'url' => [
                'scheme' => 'https',
                'host' => 'wintercms.com',
                'path' => '/',
            ],
        ]);
    }

    public function testComplexUrl()
    {
        $this->testBuiltUrl('https://user:pass@github.com:80/wintercms/winter?test=1#comment1', [
            'url' => [
                'scheme' => 'https',
                'user' => 'user',
                'pass' => 'pass',
                'host' => 'github.com',
                'port' => 80,
                'path' => '/wintercms/winter',
                'query' => 'test=1',
                'fragment' => 'comment1',
            ],
        ]);
    }

    public function testDontSquashQueryArgs()
    {
        $this->testBuiltUrl('https://user:pass@github.com:80/wintercms/winter?test=1&test=2#comment1', [
            'url' => [
                'scheme' => 'https',
                'user' => 'user',
                'pass' => 'pass',
                'host' => 'github.com',
                'port' => 80,
                'path' => '/wintercms/winter',
                'query' => 'test=1&test=2',
                'fragment' => 'comment1'
            ],
        ]);
    }

    public function testReplacements()
    {
        $this->assertEquals('https://wintercms.com/', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'wordpress.org'
        ], [
            'scheme' => 'https',
            'host' => 'wintercms.com'
        ]));

        $this->assertEquals('https://wintercms.com:80/changelog', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'wintercms.com'
        ], [
            'port' => 80,
            'path' => '/changelog'
        ]));

        $this->assertEquals('ftp://username:password@ftp.test.com.au/newfolder', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'user' => 'user',
            'pass' => 'pass',
            'host' => 'github.com',
            'port' => 80,
            'path' => '/wintercms/winter',
            'query' => 'test=1&test=2',
            'fragment' => 'comment1'
        ], [
            'scheme' => 'ftp',
            'user' => 'username',
            'pass' => 'password',
            'host' => 'ftp.test.com.au',
            'port' => 21,
            'path' => 'newfolder',
            'query' => '',
            'fragment' => ''
        ]));
    }

    public function testJoinSegments()
    {
        $this->assertEquals('https://wintercms.com/plugins/winter-pages', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'wintercms.com',
            'path' => '/plugins/'
        ], [
            'path' => 'winter-pages'
        ], HTTP_URL_JOIN_PATH));

        $this->assertEquals('https://wintercms.com/?query1=1&query2=2&query3=3', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'wintercms.com',
            'path' => '/',
            'query' => 'query1=1&query2=2'
        ], [
            'query' => 'query3=3'
        ], HTTP_URL_JOIN_QUERY));

        $this->assertEquals('https://wintercms.com/plugins/winter-pages?query1=1&query2=2&query3=3', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'wintercms.com',
            'path' => '/plugins/',
            'query' => 'query1=1&query2=2'
        ], [
            'path' => 'winter-pages',
            'query' => 'query3=3'
        ], HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY));
    }

    public function testStripSegments()
    {
        $segments = [
            'scheme' => 'https',
            'user' => 'user',
            'pass' => 'pass',
            'host' => 'github.com',
            'port' => 80,
            'path' => '/wintercms/winter',
            'query' => 'test=1&test=2',
            'fragment' => 'comment1'
        ];

        $this->assertEquals(
            'https://github.com:80/wintercms/winter?test=1&test=2#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_AUTH)
        );

        $this->assertEquals(
            'https://github.com/',
            http_build_url($segments, [], HTTP_URL_STRIP_ALL)
        );

        $this->assertEquals(
            'https://github.com:80/wintercms/winter?test=1&test=2#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_USER)
        );

        $this->assertEquals(
            'https://user@github.com:80/wintercms/winter?test=1&test=2#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_PASS)
        );

        $this->assertEquals(
            'https://user:pass@github.com/wintercms/winter?test=1&test=2#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_PORT)
        );

        $this->assertEquals(
            'https://user:pass@github.com:80/?test=1&test=2#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_PATH)
        );

        $this->assertEquals(
            'https://user:pass@github.com:80/wintercms/winter#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_QUERY)
        );

        $this->assertEquals(
            'https://user:pass@github.com:80/wintercms/winter?test=1&test=2',
            http_build_url($segments, [], HTTP_URL_STRIP_FRAGMENT)
        );

        $this->assertEquals(
            'https://user:pass@github.com/wintercms/winter',
            http_build_url($segments, [], HTTP_URL_STRIP_PORT | HTTP_URL_STRIP_QUERY | HTTP_URL_STRIP_FRAGMENT)
        );
    }
}
