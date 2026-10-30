<?php

namespace Winter\Storm\Tests\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Session;
use Winter\Storm\Auth\Manager as AuthManager;
use Winter\Storm\Support\Traits\Emitter;

/**
 * Covers how Auth\Manager treats the session identifier around a login.
 */
class ManagerSessionTest extends \Winter\Storm\Tests\TestCase
{
    protected ManagerSessionTestUser $user;

    public function setUp(): void
    {
        parent::setUp();

        ManagerSessionTestUser::$registry = [];
        $this->user = new ManagerSessionTestUser(1, 'tester');

        Session::start();
    }

    public function tearDown(): void
    {
        ManagerSessionTestManager::forgetInstance();
        ManagerSessionTestOtherManager::forgetInstance();
        ManagerSessionTestUser::$registry = [];

        parent::tearDown();
    }

    /**
     * Builds the manager the way the next request would: nothing cached in memory, the identity
     * rebuilt from whatever the session currently holds.
     */
    protected function freshManager(): ManagerSessionTestManager
    {
        ManagerSessionTestManager::forgetInstance();

        return ManagerSessionTestManager::instance();
    }

    /**
     * Emulates the next request arriving with the given session identifier: the session contents
     * are re-read from the handler rather than carried over in memory.
     */
    protected function requestWithSessionId(string $id): void
    {
        Session::save();
        Session::flush();
        Session::setId($id);
        Session::start();
    }

    public function testLoginMovesTheSessionToANewIdentifier()
    {
        $before = Session::getId();

        $this->freshManager()->login($this->user, false);

        $this->assertNotSame($before, Session::getId());
    }

    public function testLoginKeepsTheSessionContents()
    {
        Session::put('example', 'kept');
        Session::put('url.intended', 'http://localhost/somewhere');
        $token = Session::token();

        $this->freshManager()->login($this->user, false);

        $this->assertSame('kept', Session::get('example'));
        $this->assertSame('http://localhost/somewhere', Session::get('url.intended'));
        $this->assertSame($token, Session::token(), 'the CSRF token must survive the login');
        $this->assertSame([1, 'persist-code'], Session::get('winter_auth'));
    }

    public function testTheIdentifierFromBeforeTheLoginDoesNotCarryTheIdentity()
    {
        $before = Session::getId();

        $this->freshManager()->login($this->user, false);
        $this->assertTrue($this->freshManager()->check(), 'the login itself must authenticate');

        $this->requestWithSessionId($before);

        $this->assertNull(Session::get('winter_auth'));
        $this->assertFalse($this->freshManager()->check());
    }

    public function testTheIdentifierFromAfterTheLoginCarriesTheIdentity()
    {
        $this->freshManager()->login($this->user, false);
        $after = Session::getId();

        $this->requestWithSessionId($after);

        $this->assertSame([1, 'persist-code'], Session::get('winter_auth'));
        $this->assertTrue($this->freshManager()->check());
    }

    public function testOnceLeavesTheSessionAlone()
    {
        $before = Session::getId();

        $manager = $this->freshManager();

        $this->assertTrue($manager->once(['login' => 'tester', 'password' => 'secret']));
        $this->assertSame($before, Session::getId(), 'once() is stateless and must not move the session');
        $this->assertFalse(Session::has('winter_auth'));
        $this->assertSame($this->user, $manager->getUser());
    }

    public function testImpersonationLeavesTheSessionIdentifierAlone()
    {
        $impersonatee = new ManagerSessionTestUser(2, 'other');

        $manager = $this->freshManager();
        $manager->login($this->user, false);
        $signedIn = Session::getId();

        $manager = $this->freshManager();
        $manager->impersonate($impersonatee);

        $this->assertSame($signedIn, Session::getId());
        $this->assertSame($impersonatee, $manager->getUser());
        $this->assertTrue($manager->isImpersonator());
        $this->assertSame($this->user->getKey(), $manager->getRealUser()->getKey());

        $manager = $this->freshManager();
        $manager->stopImpersonate();

        $this->assertSame($signedIn, Session::getId());
        $this->assertSame($this->user->getKey(), $manager->getUser()->getKey());
        $this->assertFalse($manager->isImpersonator());
    }

    public function testASeparatelyKeyedManagerKeepsItsIdentityAcrossTheLogin()
    {
        $other = new ManagerSessionTestUser(2, 'other');

        ManagerSessionTestOtherManager::instance()->login($other, false);

        $this->freshManager()->login($this->user, false);

        ManagerSessionTestOtherManager::forgetInstance();

        $this->assertTrue(
            ManagerSessionTestOtherManager::instance()->check(),
            'the other session key must still authenticate'
        );
        $this->assertTrue($this->freshManager()->check());
    }
}

/**
 * An in-memory stand-in for an auth manager, so the session behaviour can be exercised without a
 * user table.
 */
class ManagerSessionTestManager extends AuthManager
{
    protected static $instance;

    protected $useThrottle = false;

    protected $userModel = ManagerSessionTestUser::class;

    public function createUserModel()
    {
        return new ManagerSessionTestUser(0, 'repository');
    }

    public function findUserById($id)
    {
        return ManagerSessionTestUser::$registry[$id] ?? null;
    }

    protected function validateInternal(array $credentials = [])
    {
        return ManagerSessionTestUser::$registry[1];
    }
}

class ManagerSessionTestOtherManager extends ManagerSessionTestManager
{
    protected static $instance;

    protected $sessionKey = 'other_auth';
}

class ManagerSessionTestUser implements Authenticatable
{
    use Emitter;

    /** @var array<int, ManagerSessionTestUser> */
    public static array $registry = [];

    public bool $is_activated = true;

    public function __construct(protected int $id, protected string $login)
    {
        if ($id) {
            static::$registry[$id] = $this;
        }
    }

    public function find($id)
    {
        return static::$registry[$id] ?? null;
    }

    public function getKey()
    {
        return $this->id;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function getPersistCode()
    {
        return 'persist-code';
    }

    public function checkPersistCode($code)
    {
        return $code === 'persist-code';
    }

    public function canBeImpersonated($impersonator = false)
    {
        return true;
    }

    public function clearResetPassword()
    {
    }

    public function beforeLogin()
    {
    }

    public function afterLogin()
    {
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthPassword()
    {
        return 'hashed';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName()
    {
        return 'persist_code';
    }
}
