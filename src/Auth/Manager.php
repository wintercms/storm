<?php namespace Winter\Storm\Auth;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Request;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Session\SessionManager;

/**
 * Authentication manager
 */
class Manager implements \Illuminate\Contracts\Auth\StatefulGuard
{
    use \Winter\Storm\Support\Traits\Singleton;

    /**
     * @var Models\User|null The currently logged in user
     */
    protected $user;

    /**
     * @var Models\User|null The user that is impersonating the currently logged in user when applicable
     */
    protected $impersonator;

    /**
     * @var array In memory throttle cache [md5($userId.$ipAddress) => $this->throttleModel]
     */
    protected $throttle = [];

    /**
     * @var string User Model Class
     */
    protected $userModel = Models\User::class;

    /**
     * @var string User Group Model Class
     */
    protected $groupModel = Models\Group::class;

    /**
     * @var string Throttle Model Class
     */
    protected $throttleModel = Models\Throttle::class;

    /**
     * @var bool Flag to enable login throttling
     */
    protected $useThrottle = true;

    /**
     * @var bool Internal flag to toggle using the session for the current authentication request
     */
    protected $useSession = true;

    /**
     * @var bool Flag to require users to be activated to login
     */
    protected $requireActivation = true;

    /**
     * @var string Key to store the auth session data in
     */
    protected $sessionKey = 'winter_auth';

    /**
     * @var bool Indicates if the user was authenticated via a recaller cookie.
     */
    protected $viaRemember = false;

    /**
     * @var string The IP address of this request
     */
    public $ipAddress = '0.0.0.0';

    /**
     * Session manager instance.
     */
    protected SessionManager $sessionManager;

    /**
     * Initializes the singleton
     */
    protected function init()
    {
        $this->ipAddress = Request::ip();
        $this->sessionManager = App::make(SessionManager::class);
    }

    //
    // User
    //

    /**
     * Creates a new instance of the user model
     *
     * @return Models\User
     */
    public function createUserModel()
    {
        $class = '\\'.ltrim($this->userModel, '\\');
        return new $class();
    }

    /**
     * Prepares a query derived from the user model.
     *
     * @return \Winter\Storm\Database\Builder $query
     */
    protected function createUserModelQuery()
    {
        $model = $this->createUserModel();
        /** @var \Winter\Storm\Database\Builder */
        $query = $model->newQuery();
        $this->extendUserQuery($query);

        return $query;
    }

    /**
     * Extend the query used for finding the user.
     * @param \Winter\Storm\Database\Builder $query
     * @return void
     */
    public function extendUserQuery($query)
    {
    }

    /**
     * Registers a user with the provided credentials with optional flags
     * for activating the newly created user and automatically logging them in
     *
     * @param array $credentials
     * @param bool $activate
     * @param bool $autoLogin
     * @return Models\User
     */
    public function register(array $credentials, $activate = false, $autoLogin = true)
    {
        $user = $this->createUserModel();
        $user->fill($credentials);
        $user->save();

        if ($activate) {
            $user->attemptActivation($user->getActivationCode());
        }

        // Prevents revalidation of the password field
        // on subsequent saves to this model object
        /** @phpstan-ignore-next-line */
        $user->password = null;

        if ($autoLogin) {
            $this->user = $user;
        }

        return $user;
    }

    /**
     * Determine if the guard has a user instance.
     * @return bool
     */
    public function hasUser()
    {
        return isset($this->user);
    }

    /**
     * Sets the user
     * @phpstan-param Models\User $user
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    /**
     * Returns the current user, if any.
     *
     * @return mixed (Models\User || null)
     */
    public function getUser()
    {
        if (is_null($this->user)) {
            $this->check();
        }

        return $this->user;
    }

    /**
     * Finds a user by the login value.
     *
     * @param string $id
     * @return mixed (Models\User || null)
     */
    public function findUserById($id)
    {
        $query = $this->createUserModelQuery();

        $user = $query->find($id);

        return $this->validateUserModel($user) ? $user : null;
    }

    /**
     * Finds a user by the login value.
     *
     * @param string $login
     * @return mixed (Models\User || null)
     */
    public function findUserByLogin($login)
    {
        $model = $this->createUserModel();

        $query = $this->createUserModelQuery();

        $user = $query->where($model->getLoginName(), $login)->first();

        return $this->validateUserModel($user) ? $user : null;
    }

    /**
     * Finds a user by the given credentials.
     *
     * @param array $credentials The credentials to find a user by
     * @throws AuthenticationException If the credentials are invalid
     * @return Models\User The requested user
     */
    public function findUserByCredentials(array $credentials)
    {
        $model = $this->createUserModel();
        $loginName = $model->getLoginName();

        if (!array_key_exists($loginName, $credentials)) {
            throw new AuthenticationException(sprintf('Login attribute "%s" was not provided.', $loginName));
        }

        $query = $this->createUserModelQuery();
        $hashableAttributes = $model->getHashableAttributes();
        $hashedCredentials = [];

        /*
         * Build query from given credentials
         */
        foreach ($credentials as $credential => $value) {
            // All excepted the hashed attributes
            if (in_array($credential, $hashableAttributes)) {
                $hashedCredentials = array_merge($hashedCredentials, [$credential => $value]);
            }
            else {
                $query = $query->where($credential, '=', $value);
            }
        }

        /** @var Models\User */
        $user = $query->first();
        if (!$this->validateUserModel($user)) {
            throw new AuthenticationException('A user was not found with the given credentials.');
        }

        /*
         * Check the hashed credentials match
         */
        foreach ($hashedCredentials as $credential => $value) {
            if (!$user->checkHashValue($credential, $value)) {
                // Incorrect password
                if ($credential === 'password') {
                    throw new AuthenticationException(sprintf(
                        'A user was found to match all plain text credentials however hashed credential "%s" did not match.',
                        $credential
                    ));
                }

                // User not found
                throw new AuthenticationException('A user was not found with the given credentials.');
            }
        }

        return $user;
    }

    /**
     * Perform additional checks on the user model.
     *
     * @param $user
     * @return boolean
     */
    protected function validateUserModel($user)
    {
        return $user instanceof $this->userModel;
    }

    //
    // Throttle
    //

    /**
     * Creates an instance of the throttle model
     *
     * @return Models\Throttle
     */
    public function createThrottleModel()
    {
        $class = '\\'.ltrim($this->throttleModel, '\\');
        return new $class();
    }

    /**
     * Find a throttle record by login and ip address
     *
     * @param string $loginName
     * @param string $ipAddress
     * @return Models\Throttle
     */
    public function findThrottleByLogin($loginName, $ipAddress)
    {
        $user = $this->findUserByLogin($loginName);
        if (!$user) {
            throw new AuthenticationException("A user was not found with the given credentials.");
        }

        $userId = $user->getKey();
        return $this->findThrottleByUserId($userId, $ipAddress);
    }

    /**
     * Find a throttle record by user id and ip address
     *
     * @param integer $userId
     * @param string $ipAddress
     * @return Models\Throttle
     */
    public function findThrottleByUserId($userId, $ipAddress = null)
    {
        $cacheKey = md5($userId.$ipAddress);
        if (isset($this->throttle[$cacheKey])) {
            return $this->throttle[$cacheKey];
        }

        $model = $this->createThrottleModel();
        $query = $model->where('user_id', '=', $userId);

        if ($ipAddress) {
            $query->where(function ($query) use ($ipAddress) {
                $query->where('ip_address', '=', $ipAddress);
                $query->orWhere('ip_address', '=', null);
            });
        }

        /** @var Models\Throttle|null */
        $throttle = $query->first();

        if (!$throttle) {
            $throttle = $this->createThrottleModel();
            $throttle->user_id = $userId;
            if ($ipAddress) {
                $throttle->ip_address = $ipAddress;
            }

            $throttle->save();
        }

        return $this->throttle[$cacheKey] = $throttle;
    }

    //
    // Business Logic
    //

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array $credentials The user login details
     * @param bool $remember Store a non-expire cookie for the user
     * @throws AuthenticationException If authentication fails
     * @return bool If authentication was successful
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        return !!$this->authenticate($credentials, $remember);
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return !!$this->validateInternal($credentials);
    }

    /**
     * Validate a user's credentials, method used internally.
     *
     * @param  array  $credentials
     * @return Models\User|null
     */
    protected function validateInternal(array $credentials = [])
    {
        /*
         * Default to the login name field or fallback to a hard-coded 'login' value
         */
        $loginName = $this->createUserModel()->getLoginName();
        $loginCredentialKey = isset($credentials[$loginName]) ? $loginName : 'login';

        if (empty($credentials[$loginCredentialKey])) {
            throw new AuthenticationException(sprintf('The "%s" attribute is required.', $loginCredentialKey));
        }

        if (empty($credentials['password'])) {
            throw new AuthenticationException('The password attribute is required.');
        }

        /*
         * If the fallback 'login' was provided and did not match the necessary
         * login name, swap it over
         */
        if ($loginCredentialKey !== $loginName) {
            $credentials[$loginName] = $credentials[$loginCredentialKey];
            unset($credentials[$loginCredentialKey]);
        }

        /*
         * If throttling is enabled, check they are not locked out first and foremost.
         */
        $useThrottle = $this->useThrottle;

        if ($useThrottle) {
            $throttle = $this->findThrottleByLogin($credentials[$loginName], $this->ipAddress);
            $throttle->check();
        }

        /*
         * Look up the user by authentication credentials.
         */
        try {
            $user = $this->findUserByCredentials($credentials);
        }
        catch (AuthenticationException $ex) {
            if ($useThrottle) {
                $throttle->addLoginAttempt();
            }
            $user = null;

            throw $ex;
        }

        if ($useThrottle) {
            $throttle->clearLoginAttempts();
        }

        return $user;
    }

    /**
     * Attempts to authenticate the given user according to the passed credentials.
     *
     * @param array $credentials The user login details
     * @param bool $remember Store a non-expire cookie for the user
     */
    public function authenticate(array $credentials, $remember = true)
    {
        $user = $this->validateInternal($credentials);

        $user->clearResetPassword();

        $this->login($user, $remember);

        return $this->user;
    }

    /**
     * Stores the user persistence information in the session (and cookie when $remember = true)
     *
     * @param Models\User $user
     * @param boolean $remember
     * @return void
     */
    protected function setPersistCodeInSession($user, $remember = true)
    {
        $toPersist = [$user->getKey(), $user->getPersistCode()];
        Session::put($this->sessionKey, $toPersist);

        if ($remember) {
            $config = $this->sessionManager->getSessionConfig();
            Cookie::queue(
                Cookie::forever(
                    $this->sessionKey,
                    json_encode($toPersist),
                    $config['path'],
                    $config['domain'],
                    $config['secure'] ?? false,
                    $config['http_only'] ?? true,
                    false,
                    $config['same_site'] ?? null
                )
            );
        }
    }

    /**
     * Returns the user ID and peristence code from the session or remember cookie
     *
     * @param boolean $logRemember Flag to set $this->viaRemember if the persist code was pulled from the cookie
     * @return array|null [user_id, persist_code]
     */
    protected function getPersistCodeFromSession($logRemember = false)
    {
        // Check the session first, followed by cookies
        if ($sessionArray = Session::get($this->sessionKey)) {
            $userArray = $sessionArray;
        } elseif ($cookieArray = Cookie::get($this->sessionKey)) {
            if ($logRemember) {
                $this->viaRemember = true;
            }
            $userArray = @json_decode($cookieArray, true);
        } else {
            return null;
        }

        // Validate the retrieved data ([user_id, persist_code])
        if (!is_array($userArray) || count($userArray) !== 2) {
            return null;
        }

        return $userArray;
    }

    /**
     * Check to see if the user is logged in and activated, and hasn't been banned or suspended.
     *
     * @return bool
     */
    public function check()
    {
        if (is_null($this->user)) {
            // Retrieve the user persistence information from the request
            $userArray = $this->getPersistCodeFromSession(true);
            if (!is_array($userArray) || count($userArray) !== 2) {
                return false;
            }

            list($id, $persistCode) = $userArray;

            // Retrieve the user instance
            if (!$user = $this->findUserById($id)) {
                return false;
            }

            // Validate the persitence code
            if (!$user->checkPersistCode($persistCode)) {
                return false;
            }

            // Authenticate user
            $this->user = $user;
        }

        // Validate user is activated when activation is required
        if (!($user = $this->getUser()) || ($this->requireActivation && !$user->is_activated)) {
            return false;
        }

        // Check if the user has been throttled
        if ($this->useThrottle) {
            $throttle = $this->findThrottleByUserId($user->getKey(), $this->ipAddress);

            if ($throttle->is_banned || $throttle->checkSuspended()) {
                $this->logout();
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return false;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        return $this->getUser();
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        if ($user = $this->getUser()) {
            return $user->getAuthIdentifier();
        }

        return null;
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function once(array $credentials = [])
    {
        $this->useSession = false;

        $user = $this->authenticate($credentials);

        $this->useSession = true;

        return !!$user;
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * @param  mixed  $id
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function onceUsingId($id)
    {
        if (!is_null($user = $this->findUserById($id))) {
            $this->setUser($user);

            return $user;
        }

        return false;
    }

    /**
     * Logs in the given user and sets properties
     * in the session.
     * @throws AuthenticationException If the user is not activated and $this->requireActivation = true
     * @phpstan-param Models\User $user
     */
    public function login(Authenticatable $user, $remember = true)
    {
        // Fire the 'beforeLogin' event
        $user->beforeLogin();

        // Deny users that aren't activated when activation is required
        if ($this->requireActivation && !$user->is_activated) {
            $login = $user->getLogin();
            throw new AuthenticationException(sprintf(
                'Cannot login user "%s" as they are not activated.',
                $login
            ));
        }

        $this->user = $user;

        /*
         * Create session/cookie data to persist the session
         */
        if ($this->useSession) {
            $this->setPersistCodeInSession($user, $remember);
        }

        /*
         * Fire the 'afterLogin' event
         */
        $user->afterLogin();
    }

    /**
     * Log the given user ID into the application.
     *
     * @param  mixed  $id
     * @param  bool   $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function loginUsingId($id, $remember = false)
    {
        if (!is_null($user = $this->findUserById($id))) {
            $this->login($user, $remember);

            return $user;
        }

        return false;
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember()
    {
        return $this->viaRemember;
    }

    /**
     * Logs the current user out.
     */
    public function logout()
    {
        // Initialize the current auth session before trying to remove it
        if (is_null($this->user) && !$this->check()) {
            return;
        }

        if ($this->isImpersonator()) {
            $this->user = $this->getImpersonator();
            $this->stopImpersonate();
            return;
        }

        if ($this->user) {
            $this->user->setRememberToken(null);
            $this->user->forceSave();
        }

        $this->user = null;

        Session::invalidate();
        Cookie::queue(Cookie::forget($this->sessionKey));
    }

    //
    // Impersonation
    //

    /**
     * Impersonates the given user and sets properties in the session but not the cookie.
     *
     * @param Models\User $impersonatee
     * @throws AuthorizationException If the current user is not permitted to impersonate the provided user
     * @return void
     */
    public function impersonate($impersonatee)
    {
        // If the session is already being impersonated, then use the original impersonator
        if ($this->isImpersonator()) {
            $impersonator = $this->getImpersonator() ?: false;
            $impersonatorId = $impersonator ? $impersonator->id : null;
        } else {
            // Get the current user
            $userArray = $this->getPersistCodeFromSession();
            $impersonatorId = $userArray ? $userArray[0] : null;
            $impersonator = $impersonatorId ? $this->findUserById($impersonatorId) : false;
        }

        /**
         * @event model.auth.beforeImpersonate
         * Called before the user in question is impersonated. Current user is false when either the system or a
         * user from a separate authentication system authorized the impersonation. Use this to override the results
         * of `$user->canBeImpersonated()` if desired.
         *
         * Example usage:
         *
         *     $model->bindEvent('model.auth.beforeImpersonate', function (\Winter\Storm\Auth\Models\User|false $impersonator) use (\Winter\Storm\Models\Auth\User $model) {
         *         \Log::info($impersonator->full_name . ' is attempting to impersonate ' . $model->full_name);
         *
         *         // Ignore the results of $model->canBeImpersonated() and grant impersonation access
         *         // return true;
         *
         *         // Ignore the results of $model->canBeImpersonated() and deny impersonation access
         *         // return false;
         *     });
         *
         */
        $canImpersonate = $impersonatee->fireEvent('model.auth.beforeImpersonate', [$impersonator], true);
        if (is_null($canImpersonate)) {
            $canImpersonate = $impersonatee->canBeImpersonated($impersonator);
        }

        if (!$canImpersonate) {
            throw new AuthorizationException('You cannot impersonate the selected user.');
        }

        // Impersonate the requested user by becoming them in the request & the session
        // without triggering login events that could prevent the login from succeeding
        $this->setPersistCodeInSession($impersonatee, false);
        $this->user = $impersonatee;

        // Store the current user as the impersonator if this is the first impersonation
        if (!$this->isImpersonator()) {
            Session::put($this->sessionKey . '_impersonator', $impersonatorId ?: false);
            $this->impersonator = $impersonator;
        }
    }

    /**
     * Stop the current session being impersonated and
     * authenticate as the impersonator again
     */
    public function stopImpersonate()
    {
        // Get the current user and the impersonating user
        $userArray = $this->getPersistCodeFromSession();
        $impersonateeId = $userArray ? $userArray[0] : null;
        $impersonator = $this->getImpersonator();

        if ($impersonateeId && ($impersonatee = $this->findUserById($impersonateeId))) {
            /**
             * @event model.auth.afterImpersonate
             * Called after the user in question has stopped being impersonated. Current user is false when
             * either the system or a user from a separate authentication system authorized the impersonation.
             *
             * Example usage:
             *
             *     $model->bindEvent('model.auth.afterImpersonate', function (\Winter\Storm\Auth\Models\User|false $impersonator) use (\Winter\Storm\Auth\Models\User $model) {
             *         \Log::info($impersonator->full_name . ' has stopped impersonating ' . $model->full_name);
             *     });
             *
             */
            $impersonatee->fireEvent('model.auth.afterImpersonate', [$impersonator]);
        }

        // Restore the session to the impersonator if possible
        if ($impersonator) {
            $this->setPersistCodeInSession($impersonator, false);
            $this->user = $impersonator;
        } else {
            // Impersonation via "log in as" functionality from a different user system
            // or by the system, no original user in the current auth system to restore to
            // so just forget the information that makes this request authenticated as the
            // impersonatee
            Session::forget($this->sessionKey);
            $this->user = null;
        }

        // Remove the impersonator flag
        Session::forget($this->sessionKey . '_impersonator');
        $this->impersonator = null;
    }

    /**
     * Check to see if the current session is being impersonated
     *
     * @return bool
     */
    public function isImpersonator()
    {
        return Session::has($this->sessionKey . '_impersonator');
    }

    /**
     * Get the original user doing the impersonation
     *
     * @return Models\User|false Returns the User model for the impersonator if able, `false` if not
     */
    public function getImpersonator()
    {
        if (!$this->isImpersonator()) {
            return false;
        }

        $impersonatorId = Session::get($this->sessionKey . '_impersonator');
        if ($impersonatorId === false) {
            return false;
        }

        if ($this->impersonator) {
            return $this->impersonator;
        }

        /** @var Models\User|false */
        $impersonator = $this->createUserModel()->find($impersonatorId) ?? false;

        return $this->impersonator = $impersonator;
    }

    /**
     * Gets the user for the request, taking into account impersonation
     *
     * @return mixed (Models\User || null)
     */
    public function getRealUser()
    {
        if ($impersonator = $this->getImpersonator()) {
            return $impersonator;
        } else {
            return $this->getUser();
        }
    }
}
