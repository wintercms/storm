<?php namespace Winter\Storm\Support\Facades;

use Winter\Storm\Support\Facade;

/**
 * @method static \Winter\Storm\Network\Http make(string $url, string $method, callable $options = null)
 * @method static \Winter\Storm\Network\Http get(string $url, array $options = null)
 * @method static \Winter\Storm\Network\Http post(string $url, array $options = null)
 * @method static \Winter\Storm\Network\Http delete(string $url, array $options = null)
 * @method static \Winter\Storm\Network\Http patch(string $url, array $options = null)
 * @method static \Winter\Storm\Network\Http put(string $url, array $options = null)
 * @method static \Winter\Storm\Network\Http options(string $url, array $options = null)
 * @method static \Winter\Storm\Network\Http send()
 * @method static string getRequestData()
 * @method static \Winter\Storm\Network\Http data(string $key, string $value = null)
 * @method static \Winter\Storm\Network\Http header(string $key, string $value = null)
 * @method static \Winter\Storm\Network\Http proxy(string $type, string $host, int $port, string $username = null, string $password = null)
 * @method static \Winter\Storm\Network\Http auth(string $user, string $pass = null)
 * @method static \Winter\Storm\Network\Http noRedirect()
 * @method static \Winter\Storm\Network\Http verifySSL()
 * @method static \Winter\Storm\Network\Http timeout(int $timeout)
 * @method static \Winter\Storm\Network\Http toFile(string $path, string $filter = null)
 * @method static \Winter\Storm\Network\Http setOption(string $option, string $value = null)
 *
 * @see \Winter\Storm\Network\Http
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'network.http';
    }
}
