<?php

namespace Winter\Storm\Packager;

use Winter\Packager\Composer as PackagerComposer;
use Winter\Packager\Enums\ShowMode;
use Winter\Packager\Exceptions\CommandException;
use Winter\Packager\Package\Collection;
use Winter\Packager\Package\DetailedPackage;
use Winter\Packager\Package\DetailedVersionedPackage;
use Winter\Packager\Package\Package;
use Winter\Packager\Package\VersionedPackage;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Foundation\Extension\WinterExtension;
use Winter\Storm\Support\Facades\File;

/**
 * Helper class for interacting with Composer through Winter\Packager
 * @method static Collection|DetailedVersionedPackage|DetailedPackage|VersionedPackage|Package|array|null show(?string $mode = 'installed', string $package = null, bool $noDev = false, bool $latest = false, bool $returnArray = false)
 * @method static string require(string $package, bool $dryRun = false, bool $dev = false)
 * @method static \Winter\Packager\Commands\Update update(bool $includeDev = true, bool $lockFileOnly = false, bool $ignorePlatformReqs = false, string $installPreference = 'none', bool $ignoreScripts = false, bool $dryRun = false, ?string $package = null)
 * @method static string remove(?string $package = null, bool $dryRun = false)
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
        static::$composer->setWorkDir(realpath(base_path()));

        return static::$composer;
    }

    public static function __callStatic(string $name, array $args = []): mixed
    {
        if (!isset(static::$composer)) {
            static::make();
        }

        return static::$composer->{$name}(...$args);
    }

    /**
     * Get the Winter extensions present in the current project
     * @return array $packages List of packages ['type' => ['path' => $details]]
     */
    public static function getWinterPackages(): array
    {
        return static::remember(__METHOD__, function () {
            $installed = static::show(returnArray: true);
            $packages = [];
            foreach ($installed as $package) {
                $details = static::show(package: $package['name'], returnArray: true);

                if ($package['name'] === 'winter/storm') {
                    $packages['core'][$details['path']] = $details;
                }

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

    /**
     * Get the available updates for the project
     * @return array [$package => ['from' => string, 'to' => string, 'ref' => string, 'available' => array]]
     */
    public static function getAvailableUpdates(): array
    {
        return static::remember(__METHOD__, function () {
            $upgrades = static::update(dryRun: true, withAllDependencies: true)->getUpgraded();
            $packages = static::getWinterPackageNames();

            $winterPackages = array_filter($upgrades, function ($key) use ($packages) {
                return in_array($key, $packages);
            }, ARRAY_FILTER_USE_KEY);

            foreach ($winterPackages as $name => $details) {
                $winterPackages[$name] = [
                    'from' => $details[0],
                    'to' => $details[1],
                ];

                $info = static::show(
                    package: $name,
                    mode: ShowMode::AVAILABLE,
                    latest: true,
                    returnArray: true
                );

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

    public static function getLatestSupportedVersion(string $package): string
    {
        $message = static::require(package: $package, dryRun: true);
        $output = explode(PHP_EOL, $message);
        preg_match('/Using version (.*?) /', $output[count($output) - 1], $matches);

        return $matches[1] ?? throw new CommandException('Unable to determine required version');
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

    public static function setPackageRequirement(string $package, string $version): bool
    {
        $composerJsonPath = base_path('composer.json');
        if (!File::exists($composerJsonPath)) {
            throw new ApplicationException('composer.json file does not exist.');
        }

        $json = json_decode(File::get($composerJsonPath), JSON_OBJECT_AS_ARRAY);

        $set = false;
        foreach (['require', 'require-dev'] as $mode) {
            if (isset($json[$mode][$package])) {
                $json[$mode][$package] = $version;
                $set = true;
                break;
            }
        }

        if ($set) {
            File::put($composerJsonPath, json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }

        return $set;
    }

    /**
     * This method moves the composer caching out of cache, this is so it is not invalidated during tests. @TODO: fix.
     *
     * @param string $key
     * @param callable $callable
     * @param int $expires
     * @return mixed
     */
    protected static function remember(string $key, callable $callable, int $expires = 60 * 15): mixed
    {
        $dir = temp_path('composer');

        if (!File::exists($dir)) {
            File::makeDirectory($dir);
        }

        $key = static::COMPOSER_CACHE_KEY . $key;
        $key .= File::lastModified(base_path('composer.lock')) . File::lastModified(base_path('composer.json'));

        $file = $dir . '/' . md5($key) . '.json';

        if (File::exists($file)) {
            $cache = json_decode(File::get($file), flags: JSON_OBJECT_AS_ARRAY);

            if (is_null($cache['expires']) || time() < $cache['expires']) {
                return $cache['result'];
            }
        }

        $result = $callable();

        // We don't save nothing
        if (!$result) {
            return $result;
        }

        File::put($file, json_encode([
            'expires' => $expires ? time() + $expires : null,
            'result' => $result
        ]));

        return $result;
    }
}
