<?php

namespace Winter\Storm\Tests\Foundation;

use stdClass;
use Winter\Storm\Foundation\Application;
use Winter\Storm\Tests\TestCase;

/**
 * runningInApplicationServer() gates boot-time registrations that per-request checks would skip
 * during a worker's boot. runningInConsole() cannot stand in for it: that only separates a worker
 * from an artisan command, not from an ordinary request.
 *
 * The Octane binding is referenced by name because Storm does not depend on laravel/octane.
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
     * Octane binds the client contract in worker processes only. Installing the package must not
     * be enough, or every artisan command would count as a worker.
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
     * The two answers must be independent: a process can report a console context and still be
     * serving requests.
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
     * trans(null) must not return the translator object: e(trans($key)) becomes a TypeError when
     * it does, which broke back-end pages under a worker entry point that loaded Composer's
     * autoloader (and its Laravel helpers) first.
     *
     * Asserted against Storm's Translator directly because the test suite always gets Laravel's
     * global helpers, regardless of which entry point production uses.
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
