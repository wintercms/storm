<?php namespace Winter\Storm\Router;

use Winter\Storm\Support\Str;
use Illuminate\Routing\UrlGenerator as UrlGeneratorBase;

class UrlGenerator extends UrlGeneratorBase
{
    /**
     * Generate an absolute URL to the given path.
     *
     * @param  string  $path
     * @param  mixed  $extra
     * @param  bool|null  $secure
     * @return string
     */
    public function to($path, $extra = [], $secure = null)
    {
        $url = parent::to($path, $extra, $secure);
        return static::buildUrl($url);
    }

    /**
     * Build a URL from an array returned from a `parse_url` call.
     *
     * This function serves as a counterpart to the `parse_url` method available in PHP, and a userland implementation
     * of the `http_build_url` method provided by the PECL HTTP module. This allows a developer to parse a URL to an
     * array and make adjustments to the URL parts before combining them into a valid URL reference string.
     *
     * Based off of the implentation at https://github.com/jakeasmith/http_build_url/blob/master/src/http_build_url.php
     * and https://github.com/ivantcholakov/http_build_url/blob/master/http_build_url.php
     *
     * NOTE: Key differences from the PECL implementation include NOT using the current request scheme / host / path to
     * provide fallbacks for not present values (use Url::to() instead) and supporting providing query parameters as an
     * array of values rather than just a query string.
     *
     * @see https://php.uz/manual/en/function.http-build-url.php
     * @param string|array|false|null $url The URL parts, as an array. Must match the structure returned from a `parse_url` call.
     * @param string|array|false|null $replace The URL replacement parts. Allows a developer to replace certain sections of the URL with
     *                       a different value.
     * @param int $flags A bitmask of binary or'ed HTTP_URL constants. By default, this is set to HTTP_URL_REPLACE.
     * @param array $newUrl If set, this will be filled with the array parts of the composed URL, similar to the return
     *                      value of `parse_url`.
     * @return string The generated URL as a string
     */
    public static function buildUrl($url, $replace = [], $flags = HTTP_URL_REPLACE, &$newUrl = []): string
    {
        $urlSegments = ['scheme', 'host', 'user', 'pass', 'port', 'path', 'query', 'fragment'];

        // Setup special handling for common schemes
        // @see https://en.wikipedia.org/wiki/List_of_URI_schemes for future special cases
        $singleColonSchemes = ['mailto', 'tel', 'sms'];

        // Set flags - HTTP_URL_STRIP_ALL and HTTP_URL_STRIP_AUTH cover several other flags.
        if ($flags & HTTP_URL_STRIP_ALL) {
            $flags |= HTTP_URL_STRIP_USER
                   | HTTP_URL_STRIP_PASS
                   | HTTP_URL_STRIP_PORT
                   | HTTP_URL_STRIP_PATH
                   | HTTP_URL_STRIP_QUERY
                   | HTTP_URL_STRIP_FRAGMENT;
        } elseif ($flags & HTTP_URL_STRIP_AUTH) {
            $flags |= HTTP_URL_STRIP_USER
                   | HTTP_URL_STRIP_PASS;
        }

        // Decode query parameters before parsing the URL
        $decodeQueryParams = function (string $url): string {
            if (Str::contains($url, '?')) {
                list($urlWithoutQuery, $queryArgs) = explode('?', $url, 2);
                $url = $urlWithoutQuery . '?' . urldecode($queryArgs);
            }
            return $url;
        };

        // Parse input
        if (is_string($url)) {
            $url = parse_url($decodeQueryParams($url));
        }
        if (is_string($replace)) {
            $replace = parse_url($decodeQueryParams($replace));
        }

        // Prepare input data
        $cleanUrlArray = function (&$url) use ($urlSegments) {
            if (!is_array($url)) {
                $url = [];
            }

            foreach ($url as $key => &$value) {
                // Remove invalid segments
                if (
                    !in_array($key, $urlSegments)
                    || !isset($value)
                    || (is_array($value) && !count($value))
                ) {
                    unset($url[$key]);
                    continue;
                }

                // Trim strings
                if (!is_array($value)) {
                    $value = trim((string) $value);
                }

                // Sanitize values for the port segment
                // Invalid ports are treated as if no port is set.
                if ($key === 'port') {
                    $value = (int) $value;
                    // Valid ports range from 0-65535 but 0 is a reserved port
                    // and will not actually work in a real world URL
                    if ($value < 1 || $value > 65535) {
                        $url['port'] = false;
                    }
                }
            }
        };
        $cleanUrlArray($url);
        $cleanUrlArray($replace);


        // Replace URL parts if required
        if ($flags & HTTP_URL_REPLACE) {
            $url = array_replace($url, $replace);
        } else {
            // Process joined paths
            if (
                ($flags & HTTP_URL_JOIN_PATH) &&
                isset($url['path']) &&
                isset($replace['path']) &&
                (substr($replace['path'], 0, 1) !== '/')
            ) {
                // Only join the path without the filename if the original path contained a filename
                if (substr($url['path'], -1, 1) !== '/') {
                    $basePath = str_replace('\\', '/', dirname($url['path']));
                } else {
                    $basePath = $url['path'];
                }

                // Ensure the original path ends in a slash to join the two paths
                if (substr($basePath, -1, 1) !== '/') {
                    $basePath .= '/';
                }

                $url['path'] = $basePath . $replace['path'];

                // Remove replacing path to avoid replacing the joined value
                unset($replace['path']);
            }

            // Process joined query string
            if (
                ($flags & HTTP_URL_JOIN_QUERY) &&
                isset($url['query']) &&
                isset($replace['query'])
            ) {
                $uQuery = $url['query'];
                $rQuery = $replace['query'];

                if (!is_array($uQuery)) {
                    parse_str($uQuery, $uQuery);
                }
                if (!is_array($rQuery)) {
                    parse_str($rQuery, $rQuery);
                }

                $uQuery = static::buildStr($uQuery);
                $rQuery = static::buildStr($rQuery);

                $uQuery = str_replace(array('[', '%5B'), '{{{', $uQuery);
                $uQuery = str_replace(array(']', '%5D'), '}}}', $uQuery);

                $rQuery = str_replace(array('[', '%5B'), '{{{', $rQuery);
                $rQuery = str_replace(array(']', '%5D'), '}}}', $rQuery);

                $parsedUQuery = [];
                $parsedRQuery = [];

                parse_str($uQuery, $parsedUQuery);
                parse_str($rQuery, $parsedRQuery);

                $query = static::buildStr(array_merge($parsedUQuery, $parsedRQuery));
                $query = str_replace(array('{{{', '%7B%7B%7B'), '%5B', $query);
                $query = str_replace(array('}}}', '%7D%7D%7D'), '%5D', $query);

                parse_str($query, $query);

                $url['query'] = $query;

                // Remove replacing query to avoid replacing the joined value
                unset($replace['query']);
            }
        }

        // Replace the segments that are left
        $url = array_replace($url, $replace);

        // Strip segments as necessary
        foreach ($urlSegments as $segment) {
            $strip = 'HTTP_URL_STRIP_' . strtoupper($segment);

            if (!defined($strip)) {
                continue;
            }

            if ($flags & constant($strip)) {
                unset($url[$segment]);
            }
        }

        // Make new URL available
        $newUrl = $url;

        // Generate URL string
        $urlString = '';

        // Populate the scheme section
        if (isset($url['scheme']) && $url['scheme'] !== '') {
            $urlString .= $url['scheme'];
            if (in_array($url['scheme'], $singleColonSchemes)) {
                $urlString .= ':';
            } else {
                $urlString .=  '://';
            }
        }

        // Populate the user section
        if (isset($url['user']) && $url['user'] !== '') {
            $urlString .= $url['user'];

            if (isset($url['pass']) && $url['pass'] !== '') {
                $urlString .= ':' . $url['pass'];
            }

            $urlString .= '@';
        }

        // Populate the host section
        if (isset($url['host']) && $url['host'] !== '') {
            $urlString .= $url['host'];
            // @TODO: Handle conversions into punycode for I8N hosts?
        }

        // Populate the port section
        // 0 is technically a valid port number but it is also reserved
        // so no real world URL will be able to use it.
        if (!empty($url['port'])) {
            // Ignore the port if it is the default port for the current scheme
            if ((int) getservbyname($url['scheme'], 'tcp') !== $url['port']) {
                $urlString .= ':' . $url['port'];
            }
        }

        // Populate the path section
        if (isset($url['path']) && $url['path'] !== '') {
            $segments = explode('/', $url['path']);

            // Remove ./ and resolve ../
            $dotsRemoved = [];
            foreach ($segments as $i => $segment) {
                if ($segment === '.') {
                    continue;
                } elseif ($segment === '..' && count($dotsRemoved) > 0) {
                    array_pop($dotsRemoved);
                } else {
                    $dotsRemoved[] = $segment;
                }
            }
            $segments = $dotsRemoved;

            // Ensure each segment of the path is properly encoded
            foreach ($segments as &$segment) {
                $segment = rawurlencode(rawurldecode($segment));
            }
            $path = implode('/', $segments);
        } else {
            $path = '';
        }
        if (isset($url['scheme']) && !in_array($url['scheme'], $singleColonSchemes)) {
            $urlString .= ((substr($path, 0, 1) !== '/') ? '/' : '') . $path;
        } else {
            $urlString .= $path;
        }

        // Populate the query section
        if (isset($url['query']) && $url['query'] !== '') {
            $queryParams = [];

            if (is_string($url['query'])) {
                $pairs = explode(ini_get('arg_separator.output') ?: '&', $url['query']);
                foreach ($pairs as $pair) {
                    $key = Str::before($pair, '=');
                    $value = Str::after($pair, '=');
                    if (Str::contains($pair, '=')) {
                        if (Str::contains(urldecode($pair), '[')) {
                            $queryParams[$key][] = $value;
                        } else {
                            $queryParams[$key] = $value;
                        }
                    } else {
                        $queryParams[] = $key;
                    }
                }
            } elseif (is_array($url['query'])) {
                $queryParams = $url['query'];
            }

            $urlString .= '?' . static::buildStr($queryParams);
        }

        // Populate the fragment section
        if (isset($url['fragment']) && $url['fragment'] !== '') {
            $urlString .= '#' . rawurlencode(rawurldecode($url['fragment']));
        }

        return $urlString;
    }

    /**
     * Build a query string from an array returned from a `parse_str` call.
     *
     * This function serves as a counterpart to the `parse_str` method available in PHP, and a userland implementation
     * of the `http_build_str` method provided by the PECL HTTP module. This allows a developer to parse a query string
     * to an array and make adjustments to the values before combining them into a valid query string.
     *
     * Based off of the implentation at https://github.com/ivantcholakov/http_build_url/blob/master/http_build_url.php
     *
     * @see https://php.uz/manual/en/function.http-build-str.php
     * @param array $query The query variables, as an array. Must match what would returned from a `parse_str` call.
     * @param string $prefix Optional top level prefix to prefix the variable names with
     * @param string $argSeparator Argument separator to use (by default the INI setting arg_seperator.output will be used
     *                             or "&" if neither are set)
     * @return string The generated query string
     */
    public static function buildStr(array $query, string $prefix = '', $argSeparator = null): string
    {
        if (is_null($argSeparator)) {
            $argSeparator = ini_get('arg_separator.output') ?: '&';
        }

        $result = [];

        $i = 0;
        foreach ($query as $k => $v) {
            // Handle query args with the same keys
            if ($i === $k) {
                if ($prefix) {
                    // Make sure the key is setup for array values
                    if (Str::endsWith($prefix, ']')) {
                        $key = $prefix;
                    } else {
                        $key = "{$prefix}[]";
                    }
                } else {
                    // Handle query args without values
                    $key = $v;
                    $v = null;
                }
            } else {
                // Handle query args with named array elements
                $key = $prefix ? "{$prefix}[{$k}]" : $k;
            }

            if (is_array($v)) {
                $result[] = static::buildStr($v, $key, $argSeparator);
            } else {
                $result[] = rawurlencode(rawurldecode($key)) . (isset($v) ? '=' . rawurlencode(rawurldecode($v)) : '');
            }
            $i++;
        }

        return implode($argSeparator, $result);
    }
}
