<?php

namespace Winter\Storm\Tests\Foundation;

use Illuminate\Http\Request;
use ReflectionObject;
use RuntimeException;
use Winter\Storm\Auth\Manager as AuthManager;
use Winter\Storm\Contracts\ResetsWorkerState;
use Winter\Storm\Exception\ErrorHandler;
use Winter\Storm\Halcyon\MemoryCacheManager;

/**
 * State that a single operation produces must not survive into the next one.
 *
 * Under PHP-FPM each request gets a fresh process, so these are all harmless. Under a persistent
 * application server the same objects and static properties serve every request, and anything
 * derived from one request stays visible to the next.
 */
class WorkerStateTest extends \Winter\Storm\Tests\TestCase
{
    protected function setProtected(object $target, string $property, $value): void
    {
        $prop = (new ReflectionObject($target))->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($target, $value);
    }

    protected function getProtected(object $target, string $property)
    {
        $prop = (new ReflectionObject($target))->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($target);
    }

    //
    // Auth manager
    //

    public function testAuthManagerImplementsTheResetContract()
    {
        $this->assertInstanceOf(ResetsWorkerState::class, AuthManager::instance());
    }

    public function testResettingTheAuthManagerClearsRequestDerivedState()
    {
        $manager = AuthManager::instance();

        $this->setProtected($manager, 'user', new \stdClass());
        $this->setProtected($manager, 'impersonator', new \stdClass());
        $this->setProtected($manager, 'throttle', ['abc' => new \stdClass()]);
        $this->setProtected($manager, 'viaRemember', true);
        $manager->ipAddress = '203.0.113.9';

        $manager->resetWorkerState();

        $this->assertNull($this->getProtected($manager, 'user'), 'the resolved user must not persist');
        $this->assertNull($this->getProtected($manager, 'impersonator'), 'impersonation must not persist');
        $this->assertSame([], $this->getProtected($manager, 'throttle'));
        $this->assertFalse($this->getProtected($manager, 'viaRemember'));
        $this->assertNotSame('203.0.113.9', $manager->ipAddress);
    }

    /**
     * Configuration belongs to the worker, not to a request, so it must survive a reset.
     */
    public function testResettingTheAuthManagerPreservesConfiguration()
    {
        $manager = AuthManager::instance();

        $userModel = $this->getProtected($manager, 'userModel');
        $sessionKey = $this->getProtected($manager, 'sessionKey');
        $useThrottle = $this->getProtected($manager, 'useThrottle');

        $manager->resetWorkerState();

        $this->assertSame($userModel, $this->getProtected($manager, 'userModel'));
        $this->assertSame($sessionKey, $this->getProtected($manager, 'sessionKey'));
        $this->assertSame($useThrottle, $this->getProtected($manager, 'useThrottle'));
    }

    public function testResettingTheAuthManagerIsIdempotent()
    {
        $manager = AuthManager::instance();

        $manager->resetWorkerState();
        $manager->resetWorkerState();

        $this->assertNull($this->getProtected($manager, 'user'));
        $this->assertSame([], $this->getProtected($manager, 'throttle'));
    }

    /**
     * once() disables session persistence for a single authentication attempt. A throw used to
     * leave it disabled for every later caller in the process.
     */
    public function testOnceRestoresSessionUseWhenAuthenticationThrows()
    {
        $manager = WorkerStateThrowingAuthManager::instance();

        $this->assertTrue($this->getProtected($manager, 'useSession'));

        try {
            $manager->once(['login' => 'someone', 'password' => 'secret']);
            $this->fail('The throwing manager should have propagated its exception.');
        }
        catch (RuntimeException $ex) {
            // Expected.
        }

        $this->assertTrue(
            $this->getProtected($manager, 'useSession'),
            'a thrown authentication attempt must not leave session use disabled'
        );
    }

    public function testOnceRestoresTheConfiguredValueRatherThanHardCodingTrue()
    {
        WorkerStateThrowingAuthManager::forgetInstance();
        $manager = WorkerStateThrowingAuthManager::instance();
        $this->setProtected($manager, 'useSession', false);

        try {
            $manager->once(['login' => 'someone', 'password' => 'secret']);
        }
        catch (RuntimeException $ex) {
            // Expected.
        }

        $this->assertFalse(
            $this->getProtected($manager, 'useSession'),
            'the previous value must be restored, not replaced with true'
        );
    }

    //
    // Error handler masks
    //

    public function testResettingMaskStateDiscardsUnbalancedMasks()
    {
        ErrorHandler::applyMask(new RuntimeException('first'));
        ErrorHandler::applyMask(new RuntimeException('second'));

        ErrorHandler::resetMaskState();

        $activeMask = (new \ReflectionClass(ErrorHandler::class))->getProperty('activeMask');
        $activeMask->setAccessible(true);
        $maskLayers = (new \ReflectionClass(ErrorHandler::class))->getProperty('maskLayers');
        $maskLayers->setAccessible(true);

        $this->assertNull($activeMask->getValue());
        $this->assertSame([], $maskLayers->getValue());
    }

    public function testResettingMaskStateIsIdempotent()
    {
        ErrorHandler::resetMaskState();
        ErrorHandler::resetMaskState();

        $activeMask = (new \ReflectionClass(ErrorHandler::class))->getProperty('activeMask');
        $activeMask->setAccessible(true);

        $this->assertNull($activeMask->getValue());
    }

    //
    // Static extension dispatch
    //

    /**
     * The called class is stored statically while a static extension method runs, so a throw used
     * to leave the previous caller's class name visible to every later static call.
     */
    public function testStaticCalledClassIsClearedWhenAnExtensionThrows()
    {
        WorkerStateStaticExtension::$extendableStaticCalledClass = 'PreviousCaller';

        try {
            WorkerStateExtendableHost::explode();
            $this->fail('The extension should have propagated its exception.');
        }
        catch (RuntimeException $ex) {
            // Expected.
        }

        $this->assertSame(
            'PreviousCaller',
            WorkerStateStaticExtension::$extendableStaticCalledClass,
            'a thrown static extension call must restore the previous called class'
        );
    }

    //
    // Halcyon request cache
    //

    /**
     * runningInConsole() only reports the SAPI, so an application server that boots from the CLI
     * and then serves HTTP would otherwise have the request cache silently disabled.
     */
    public function testRequestCacheIsEnabledForAnApplicationServerDespiteTheConsoleSapi()
    {
        $this->app['config']->set('cache.disableRequestCache', null);

        $this->assertTrue($this->app->runningInConsole(), 'the test suite runs under the CLI SAPI');
        $this->assertFalse(MemoryCacheManager::isEnabled());

        $this->app->instance('Laravel\Octane\Contracts\Client', new \stdClass());

        $this->assertTrue(
            MemoryCacheManager::isEnabled(),
            'a worker serving HTTP must get the request cache it was adopted for'
        );

        $this->app->forgetInstance('Laravel\Octane\Contracts\Client');
    }

    public function testAnExplicitDisableStillWins()
    {
        $this->app->instance('Laravel\Octane\Contracts\Client', new \stdClass());
        $this->app['config']->set('cache.disableRequestCache', true);

        $this->assertFalse(MemoryCacheManager::isEnabled());

        $this->app['config']->set('cache.disableRequestCache', null);
        $this->app->forgetInstance('Laravel\Octane\Contracts\Client');
    }
}

/**
 * An auth manager whose authentication always fails, standing in for invalid credentials or a
 * failure inside a user model event.
 */
class WorkerStateThrowingAuthManager extends AuthManager
{
    public function authenticate(array $credentials, $remember = true)
    {
        throw new RuntimeException('authentication exploded');
    }
}

class WorkerStateExtendableHost
{
    use \Winter\Storm\Extension\ExtendableTrait;

    /**
     * Static methods on the listed extensions are dispatched through extendableCallStatic().
     */
    public $implement = ['Winter\\Storm\\Tests\\Foundation\\WorkerStateStaticExtension'];

    public static function __callStatic($name, $params)
    {
        return self::extendableCallStatic($name, $params);
    }
}

class WorkerStateStaticExtension
{
    use \Winter\Storm\Extension\ExtensionTrait;

    public static function explode()
    {
        throw new RuntimeException('static extension exploded');
    }
}
