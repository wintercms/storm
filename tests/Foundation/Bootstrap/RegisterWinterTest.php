<?php

namespace Winter\Storm\Tests\Foundation\Bootstrap;

use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Winter\Storm\Foundation\Application;
use Winter\Storm\Foundation\Bootstrap\RegisterWinter;

/**
 * RegisterWinter forces the configured root URL when no request is bound to derive one from.
 * The gate must be the request itself, not runningInConsole(): a worker boots through the HTTP
 * kernel before any request exists, and the override must not fire against the requests it
 * serves afterwards.
 */
class RegisterWinterTest extends \Winter\Storm\Tests\TestCase
{
    /**
     * A bare application, which registers Storm's routing provider (and therefore the real `url`
     * binding) but has performed no HTTP bootstrapping.
     */
    protected function makeApplication(): Application
    {
        $app = new Application(__DIR__);

        $app->instance('config', new Repository([
            'app' => ['url' => 'https://configured.example', 'asset_url' => null],
            'cms' => [],
        ]));

        return $app;
    }

    /**
     * The regression: bootstrapping in console with no request bound must not throw.
     */
    public function testBootstrappingInConsoleWithoutARequestDoesNotThrow()
    {
        $app = $this->makeApplication();

        $this->assertTrue($app->runningInConsole());
        $this->assertFalse($app->bound('request'));

        (new RegisterWinter())->bootstrap($app);

        $this->assertTrue($app->bound('string'));
        $this->assertTrue($app->bound('svg'));
    }

    /**
     * Skipping the workaround is also correct on its own terms: each served request supplies its
     * own root URL, so the configured one must not be pinned for the worker's lifetime.
     */
    public function testTheConfiguredRootUrlIsNotForcedWhenNoRequestIsBound()
    {
        $app = $this->makeApplication();

        (new RegisterWinter())->bootstrap($app);

        $app->instance('request', Request::create('https://served.example/some/page', 'GET'));

        $this->assertStringStartsWith('https://served.example', $app['url']->to('/other'));
    }

    /**
     * Existing console behaviour is preserved when a request has already been bound, which is
     * what the console kernel guarantees via SetRequestForConsole.
     */
    public function testTheConfiguredRootUrlIsStillForcedWhenARequestIsBound()
    {
        $app = $this->makeApplication();
        $app->instance('request', Request::create('http://localhost/artisan', 'GET'));

        (new RegisterWinter())->bootstrap($app);

        /*
         * forceRootUrl() overrides the host but leaves the scheme to the request, so assert on the
         * forced host rather than the full URL.
         */
        $url = $app['url']->to('/other');

        $this->assertStringContainsString('configured.example', $url);
        $this->assertStringNotContainsString('localhost', $url);
    }

    /**
     * The rest of the bootstrapper must run regardless of the URL workaround.
     */
    public function testConfiguredPathsAreStillApplied()
    {
        $app = $this->makeApplication();
        $app['config']->set('cms.pluginsPathLocal', '/tmp/register-winter-plugins');
        $app['config']->set('cms.themesPathLocal', '/tmp/register-winter-themes');

        (new RegisterWinter())->bootstrap($app);

        $this->assertStringContainsString('register-winter-plugins', $app->pluginsPath());
        $this->assertStringContainsString('register-winter-themes', $app->themesPath());
    }
}
