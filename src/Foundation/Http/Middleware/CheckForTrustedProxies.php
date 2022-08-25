<?php namespace Winter\Storm\Foundation\Http\Middleware;

use Winter\Storm\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Enables support for trusted proxies in requests.
 *
 * Allows for trusted proxies to provide secure assets in a request even if PHP itself is declaring that HTTPS is
 * inactive, which is the case for a lot of common DNS, caching and load balancing providers such as Amazon Elastic
 * Load Balancing, CloudFlare, etc.
 *
 * Proxies should be defined in the `config/app.php` file within the `trustedProxies` configuration variable.
 *
 * Based off the implementation from https://github.com/fideloper/TrustedProxy.
 *
 * @see https://github.com/wintercms/winter/issues/232.
 * @author Winter CMS
 */
class CheckForTrustedProxies
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle(Request $request, \Closure $next)
    {
        $request::setTrustedProxies([], $this->getTrustedHeaders()); // Reset trusted proxies between requests
        $this->setTrustedProxies($request);

        return $next($request);
    }

    /**
     * Get proxies defined in configuration.
     *
     * @return array|string|false|null
     */
    public function proxies()
    {
        return Config::get('app.trustedProxies', null);
    }

    /**
     * Get proxy headers to trust.
     *
     * @return int
     */
    public function headers()
    {
        return Config::get('app.trustedProxyHeaders', -1);
    }

    /**
     * Sets the trusted proxies for the request based on the `app.trustedProxies` configuration option.
     *
     * @param Request $request
     * @return void
     */
    protected function setTrustedProxies(Request $request)
    {
        $proxies = $this->proxies();

        // If no proxies are trusted (or no headers are trusted), skip this process.
        if (is_null($proxies) || $proxies === false || $this->getTrustedHeaders() === -1) {
            return;
        }

        // If any proxy is allowed, set the current calling IP as allowed.
        if ($proxies === '*') {
            $this->allowProxies($request, [
                $request->server->get('REMOTE_ADDR')
            ]);
            return;
        }
        
        // If all proxies are allowed, open the floodgates
        if ($proxies === '**') {
            $this->allowProxies($request, ['0.0.0.0/0', '2000:0:0:0:0:0:0:0/3']);
            return;
        }

        // Support comma-separated strings as well as arrays
        $proxies = (is_string($proxies))
            ? array_map('trim', explode(',', $proxies))
            : $proxies;

        if (is_array($proxies)) {
            $this->allowProxies($request, $proxies);
        }
    }

    /**
     * Allows the given IP addresses to be trusted as proxies.
     *
     * @param Request $request
     * @param array $proxies
     * @return void
     */
    protected function allowProxies(Request $request, array $proxies)
    {
        $request->setTrustedProxies($proxies, $this->getTrustedHeaders());
    }

    /**
     * Retrieve trusted headers, falling back to trusting no headers, effectively disallowing all proxies.
     *
     * @return int A bit field of Request::HEADER_*, to set which headers to trust from your proxies.
     */
    protected function getTrustedHeaders()
    {
        $headers = $this->headers();

        switch ($headers) {
            case 'HEADER_FORWARDED':
            case Request::HEADER_FORWARDED:
                return Request::HEADER_FORWARDED;

            case 'HEADER_X_FORWARDED_FOR':
            case Request::HEADER_X_FORWARDED_FOR:
                return Request::HEADER_X_FORWARDED_FOR;

            case 'HEADER_X_FORWARDED_HOST':
            case Request::HEADER_X_FORWARDED_HOST:
                return Request::HEADER_X_FORWARDED_HOST;

            case 'HEADER_X_FORWARDED_PROTO':
            case Request::HEADER_X_FORWARDED_PROTO:
                return Request::HEADER_X_FORWARDED_PROTO;

            case 'HEADER_X_FORWARDED_PORT':
            case Request::HEADER_X_FORWARDED_PORT:
                return Request::HEADER_X_FORWARDED_PORT;

            case 'HEADER_X_FORWARDED_PREFIX':
            case Request::HEADER_X_FORWARDED_PREFIX:
                return Request::HEADER_X_FORWARDED_PREFIX;

            case 'HEADER_X_FORWARDED_ALL':
                return Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO;

            case 'HEADER_X_FORWARDED_AWS_ELB':
            case Request::HEADER_X_FORWARDED_AWS_ELB:
                return Request::HEADER_X_FORWARDED_AWS_ELB;

            case 'HEADER_X_FORWARDED_TRAEFIK':
            case Request::HEADER_X_FORWARDED_TRAEFIK:
                return Request::HEADER_X_FORWARDED_TRAEFIK;
        }

        return $headers;
    }
}
