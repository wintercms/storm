<?php

namespace Winter\Storm\Tests\Auth;

use ReflectionProperty;
use RuntimeException;
use Winter\Storm\Auth\Manager as AuthManager;

class ManagerOnceTest extends \Winter\Storm\Tests\TestCase
{
    public function tearDown(): void
    {
        ManagerOnceTestThrowingManager::forgetInstance();

        parent::tearDown();
    }

    public function testOnceRestoresSessionUseWhenAuthenticationThrows()
    {
        $manager = ManagerOnceTestThrowingManager::instance();

        $this->assertTrue($this->getUseSession($manager));

        try {
            $manager->once(['login' => 'someone', 'password' => 'secret']);
            $this->fail('The exception from authenticate() should propagate.');
        }
        catch (RuntimeException $ex) {
            // Expected.
        }

        $this->assertTrue($this->getUseSession($manager));
    }

    public function testOnceRestoresThePreviousValueRatherThanTrue()
    {
        $manager = ManagerOnceTestThrowingManager::instance();
        (new ReflectionProperty($manager, 'useSession'))->setValue($manager, false);

        try {
            $manager->once(['login' => 'someone', 'password' => 'secret']);
        }
        catch (RuntimeException $ex) {
            // Expected.
        }

        $this->assertFalse($this->getUseSession($manager));
    }

    protected function getUseSession(AuthManager $manager): bool
    {
        return (new ReflectionProperty($manager, 'useSession'))->getValue($manager);
    }
}

class ManagerOnceTestThrowingManager extends AuthManager
{
    public function authenticate(array $credentials, $remember = true)
    {
        throw new RuntimeException('authentication failed');
    }
}
