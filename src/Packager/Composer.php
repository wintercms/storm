<?php

namespace Winter\Storm\Packager;

use Illuminate\Support\Facades\Cache;
use Winter\Packager\Composer as PackagerComposer;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Foundation\Extension\WinterExtension;
use Winter\Storm\Network\Http;
use Winter\Storm\Packager\Commands\InfoCommand;
use Winter\Storm\Packager\Commands\RemoveCommand;
use Winter\Storm\Packager\Commands\RequireCommand;
use Winter\Storm\Packager\Commands\SearchCommand;
use Winter\Storm\Packager\Commands\ShowCommand;
use Winter\Storm\Packager\Commands\UpdateCommand;
use Winter\Storm\Support\Facades\File;

/**
 * @class Composer
 * @method static i(): array
 * @method static install(): array
 * @method static search(string $query, ?string $type = null, bool $onlyNames = false, bool $onlyVendors = false): \Winter\Packager\Commands\Search
 * @method static info(?string $package = null, bool $all = false, bool $latest = false): array
 * @method static show(?string $mode = 'installed', string $package = null, bool $noDev = false, bool $path = false): object
 * @method static update(bool $includeDev = true, bool $lockFileOnly = false, bool $ignorePlatformReqs = false, string $installPreference = 'none', bool $ignoreScripts = false, bool $dryRun = false, ?string $package = null): \Winter\Packager\Commands\Update
 * @method static remove(?string $package = null, bool $dryRun = false): array
 * @method static require(?string $package = null, bool $dryRun = false, bool $dev = false): string
 * @method static version(string $detail = 'version'): array<string, string>|string
 */
class Composer
{
    public const COMPOSER_CACHE_KEY = 'winter.system.composer';

    protected static PackagerComposer $composer;

    public static function make(bool $fresh = false): PackagerComposer
    {
        if (!$fresh && isset(static::$composer)) {
            return static::$composer;
        }

        static::$composer = new PackagerComposer();
        static::$composer->setWorkDir(base_path());

        static::$composer->setCommand('remove', RemoveCommand::class);
        static::$composer->setCommand('require', RequireCommand::class);
        static::$composer->setCommand('search', SearchCommand::class);
        static::$composer->setCommand('show', ShowCommand::class);
        static::$composer->setCommand('info', new InfoCommand(static::$composer));
        static::$composer->setCommand('update', UpdateCommand::class);

        return static::$composer;
    }

    public static function __callStatic(string $name, array $args = []): mixed
    {
        if (!isset(static::$composer)) {
            static::make();
        }

        return static::$composer->{$name}(...$args);
    }

    public static function getWinterPackages(): array
    {
        return static::remember('packages', function () {
            $installed = static::info();
            $packages = [];
            foreach ($installed as $package) {
                $details = static::info($package['name']);

                $type = match ($details['type']) {
                    'winter-plugin', 'october-plugin' => 'plugins',
                    'winter-module', 'october-module' => 'modules',
                    'winter-theme', 'october-theme' => 'themes',
                    default => null
                };

                if (!$type) {
                    continue;
                }

                $packages[$type][$details['path']] = $details;
            }

            return $packages;
        });
    }

    public static function getAvailableUpdates(): array
    {
        return static::remember(__METHOD__, function () {
            $upgrades = static::update(dryRun: true)->getUpgraded();
            $packages = static::getWinterPackageNames();

            $winterPackages = array_filter($upgrades, function ($key) use ($packages) {
                return in_array($key, $packages);
            }, ARRAY_FILTER_USE_KEY);

            foreach ($winterPackages as $name => $details) {
                $winterPackages[$name] = [
                    'from' => $details[0],
                    'to' => $details[1],
                ];

                $info = static::info($name, all: true, latest: true);

                $winterPackages[$name] = [
                    'from' => $details[0],
                    'to' => $details[1],
                    'ref' => $info['dist']['reference'] ?? null,
                    'available' => static::filterProductionVersions($info['versions'], [$details[0]]),
                ];
            }

            return $winterPackages;
        });
    }

    public static function filterProductionVersions(array $versions, array $keep = []): array
    {
        foreach ($versions as $index => $version) {
            if ((!str_starts_with($version, 'v') || str_ends_with($version, '-dev')) && !in_array($version, $keep)) {
                unset($versions[$index]);
            }
        }

        usort($versions, fn (string $a, string $b): int => version_compare($a, $b, '<'));

        return $versions;
    }

    public static function updateAvailable(string $package): bool
    {
        return isset(static::getAvailableUpdates()[$package]);
    }

    public static function getPackageInfoByExtension(WinterExtension $extension): array
    {
        return static::getPackageInfoByPath($extension->getPath());
    }

    public static function getPackageNameByExtension(WinterExtension $extension): ?string
    {
        return static::getPackageInfoByPath($extension->getPath())['name'];
    }

    public static function getPackageInfoByPath(string $path): array
    {
        return array_merge(...array_values(static::getWinterPackages()))[$path] ?? [];
    }

    public static function getWinterPackageNames(): array
    {
        return array_values(
            array_map(
                fn ($package) => $package['name'],
                array_merge(...array_values(static::getWinterPackages()))
            )
        );
    }

    public static function getWinterPackagesWithVersion(): array
    {
        $packages = [];
        foreach (array_merge(...array_values(static::getWinterPackages())) as $package) {
            $packages[$package['name']] = [
                'version' => $package['versions'][0] ?? null,
                'ref' => $package['dist']['reference'] ?? null
            ];
        }

        return $packages;
    }

    /**
     * This method moves the composer caching out of cache, this is so it is not invalidated during tests. @TODO: fix.
     *
     * @param string $key
     * @param callable $callable
     * @param int $expires
     * @return mixed
     */
    protected static function remember(string $key, callable $callable, int $expires = 60 * 5): mixed
    {
        $dir = temp_path('composer');

        if (!File::exists($dir)) {
            File::makeDirectory($dir);
        }

        $key = static::COMPOSER_CACHE_KEY . $key . File::lastModified(base_path('composer.lock'));
        $file = $dir . '/' . md5($key) . '.cache';

        if (File::exists($file)) {
            $cache = unserialize(File::get($file));

            if (is_null($cache['expires']) || time() < $cache['expires']) {
                return $cache['result'];
            }
        }

        $result = $callable();

        File::put($file, serialize([
            'expires' => $expires ? time() + $expires : null,
            'result' => $result
        ]));

        return $result;
    }

    public static function listPackages(string $type): array
    {
        return Cache::remember(static::COMPOSER_CACHE_KEY . '.packages.' . $type, 60 * 60 * 24, function () use ($type) {
            $page = 0;
            $packages = [];
            do {
                $result = Http::get('https://packagist.org/search.json', function (Http $http) use (&$page, $type) {
                    $http->data([
                        'q' => '',
                        'page' => ++$page,
                        'type' => $type
                    ]);
                });

                if ($result->code != '200') {
                    throw new ApplicationException('Unable to retrieve packages, failed with code: ' . $result->code);
                }

                $data = json_decode($result->body, JSON_OBJECT_AS_ARRAY);

                $packages = array_merge($packages, $data['results']);
            } while (isset($data['next']));

            return $packages;
        });
    }
}
