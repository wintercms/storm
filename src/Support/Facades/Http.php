<?php namespace Winter\Storm\Support\Facades;

use Winter\Storm\Support\Facade;
use Winter\Storm\Network\Http as NetworkHttp;

/**
 * Facade for the Http network access class.
 *
 * Static methods:
 *
 * @method static NetworkHttp make(string $url, string $method, callable|null $options = null) Create a new Http instance with the given URL and method.
 * @method static NetworkHttp get(string $url, callable|null $options = null) Perform a GET request.
 * @method static NetworkHttp post(string $url, callable|null $options = null) Perform a POST request.
 * @method static NetworkHttp delete(string $url, callable|null $options = null) Perform a DELETE request.
 * @method static NetworkHttp patch(string $url, callable|null $options = null) Perform a PATCH request.
 * @method static NetworkHttp put(string $url, callable|null $options = null) Perform a PUT request.
 * @method static NetworkHttp options(string $url, callable|null $options = null) Perform an OPTIONS request.
 * @method static NetworkHttp head(string $url, callable|null $options = null) Perform a HEAD request.
 *
 * Instance methods:
 *
 * @method NetworkHttp send() Execute the HTTP request.
 * @method string getRequestData() Return the request data set.
 * @method NetworkHttp json(mixed $payload) Add JSON encoded payload.
 * @method NetworkHttp data(array|string $key, array|string|null $value = null) Add data to the request.
 * @method NetworkHttp header(array|string $key, array|string|null $value = null) Add a header to the request.
 * @method NetworkHttp proxy(string $type, string $host, int $port, string|null $username = null, string|null $password = null) Set a proxy for the request.
 * @method NetworkHttp auth(string $user, string|null $pass = null) Add authentication to the request.
 * @method NetworkHttp noRedirect() Disable redirects.
 * @method NetworkHttp verifySSL() Enable SSL verification.
 * @method NetworkHttp timeout(int $timeout) Set the request timeout.
 * @method NetworkHttp toFile(string $path, string|null $filter = null) Write the response to a file.
 * @method NetworkHttp setOption(array|int|string $option, mixed $value = null) Add single or multiple CURL options to the request.
 * @method string __toString() Get the last response body.
 *
 * Properties:
 *
 * @property string $body The last response body.
 * @property int $code The last returned HTTP code.
 * @property bool $ok Indicates if the last response was successful (HTTP 2xx).
 * @property array $headers The headers from the last response.
 * @property array $info The cURL response information.
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
