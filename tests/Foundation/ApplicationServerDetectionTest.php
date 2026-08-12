<?php

namespace Winter\Storm\Tests\Foundation;

use stdClass;
use Winter\Storm\Foundation\Application;
use Winter\Storm\Tests\TestCase;

/**
 * A worker's providers register once, from a CLI context, and then serve requests of every kind.
 *
 * Anything gated on a per-request condition at registration time is therefore decided by the synthetic
 * boot request and never revisited. runningInBackend() is the case that matters: it is false during a
 * worker's boot, so the back-end registrations behind it never ran at all, and any back-end page
 * needing a form widget failed. Winter's module providers now also admit an application server, which
 * makes this detection load-bearing.
 *
 * runningInConsole() cannot serve the purpose, because a worker is started from the CLI and so answers
 * true — which is precisely why this is a separate method.
 *
 * The binding is referenced by name rather than by importing the interface: Storm does not depend on
 * laravel/octane, and must not start doing so for a test.
 */
class ApplicationServerDetectionTest extends TestCase
{
    protected const OCTANE_CLIENT = 'Laravel\Octane\Contracts\Client';

    protected function makeApplication(): Application
    {
        return new Application(__DIR__);
    }

    public function testAPlainApplicationIsNotAnApplicationServer()
    {
        $this->assertFalse($this->makeApplication()->runningInApplicationServer());
    }

    /**
     * Octane binds the client contract in the worker process only, so the package merely being
     * installed must not be enough — otherwise every artisan command would be treated as a worker.
     */
    public function testDetectionIsDrivenByTheBindingRatherThanThePackage()
    {
        $app = $this->makeApplication();

        $this->assertFalse(
            $app->runningInApplicationServer(),
            'Nothing is bound yet, so this must be false whether or not laravel/octane is installed.'
        );

        $app->instance(static::OCTANE_CLIENT, new stdClass());

        $this->assertTrue($app->runningInApplicationServer());
    }

    /**
     * The distinction the method exists for: a worker is a console process too, so the two answers
     * have to be able to disagree.
     */
    public function testAnApplicationServerIsDetectedEvenThoughItIsAlsoAConsoleProcess()
    {
        $app = $this->makeApplication();
        $app->instance(static::OCTANE_CLIENT, new stdClass());

        $this->assertTrue($app->runningInConsole(), 'The test suite itself runs in the console.');
        $this->assertTrue(
            $app->runningInApplicationServer(),
            'A worker is started from the CLI, so this must not be derived from the console answer.'
        );
    }

    /**
     * The global trans() helper Storm defines must not return an object for a null key.
     *
     * Laravel's helper returns the translator instance in that case, and e() then raises a TypeError
     * on it — which is what broke every back-end page with a settings menu when a worker entry point
     * loaded Composer's autoloader before bootstrap/autoload.php and so got Laravel's definition.
     *
     * Asserted against Storm's Translator rather than the global helper on purpose: vendor/bin/phpunit
     * requires Composer's autoloader before the suite's bootstrap file, so inside the test suite the
     * global helpers are always Laravel's regardless of which entry point production uses. The
     * behaviour that has to hold is this method's.
     */
    public function testTranslatingANullKeyDoesNotReturnAnObject()
    {
        $translator = new \Winter\Storm\Translation\Translator(
            new \Winter\Storm\Translation\FileLoader(
                new \Winter\Storm\Filesystem\Filesystem(),
                __DIR__
            ),
            'en'
        );

        $translated = $translator->trans(null);

        $this->assertNotInstanceOf(
            \Winter\Storm\Translation\Translator::class,
            $translated,
            'Returning the translator itself makes e(trans($value)) a TypeError for any null value.'
        );
        $this->assertTrue(
            $translated === null || is_string($translated) || is_array($translated),
            'trans() must yield something e() can escape.'
        );
    }
}
