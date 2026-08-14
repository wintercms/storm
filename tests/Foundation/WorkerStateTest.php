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
 * Request state must not survive into the next request when the process is reused, as it is
 * under a persistent application server.
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

        // Recapturing here would run before the trusted-proxy middleware, so the reset leaves
        // the address unresolved and getIpAddress() derives it on first use.
        $this->assertNull(
            $manager->ipAddress,
            'the reset must leave the IP unresolved rather than sampling the request too early'
        );

        $resolved = $manager->getIpAddress();
        $this->assertNotSame('203.0.113.9', $resolved, 'the previous operation\'s address must not survive');
        $this->assertSame(
            $resolved,
            $manager->ipAddress,
            'first use must memoize the derived address for the rest of the operation'
        );
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
     * once() disables session persistence for a single attempt and must re-enable it even when
     * authentication throws.
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
     * extendableCallStatic() stores the called class in a static while it runs, and must restore
     * it when the extension method throws.
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
     * The request cache is disabled for console runs, but a worker serving HTTP is not a console
     * run even when the process looks like one.
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
 * Fails every authentication attempt.
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
