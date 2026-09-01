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
use Winter\Storm\Foundation\Extension\WinterExtension;

/**
 * Helper class for interacting with Composer through Winter\Packager
 * @method static Collection|DetailedVersionedPackage|DetailedPackage|VersionedPackage|Package|array|null show(ShowMode $mode = ShowMode::INSTALLED, string $package = null, bool $noDev = false, bool $latest = false, bool $returnArray = false)
 * @method static string require(string $package, bool $dryRun = false, bool $dev = false, bool $noUpdate = false, bool $noScripts = false)
 * @method static \Winter\Packager\Commands\Update update(bool $includeDev = true, bool $lockFileOnly = false, bool $ignorePlatformReqs = false, string $installPreference = 'none', bool $ignoreScripts = false, bool $dryRun = false, ?string $package = null, bool $withAllDependencies = false)
 * @method static string remove(?string $package = null, bool $dryRun = false)
 */
class Composer
{
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
     * Pin the provided package to the provided version range. If no version range is provided then
     * Composer will use whatever it would use if require $package was run.
     */
    public static function pin(string $package, ?string $version = null): void
    {
        $requiredPackage = $package;
        if (!is_null($version)) {
            $requiredPackage .= ":$version";
        }
        static::require($requiredPackage, noUpdate: true, noScripts: true);
    }

    /**
     * Get the Winter extensions present in the current project
     * @return array $packages List of packages ['type' => ['path' => $details]]
     */
    public static function getWinterPackages(): array
    {
        $installed = static::make()->getInstalledFile()->packages;
        $packages = [];
        foreach ($installed as $name => $details) {
            $type = null;
            if ($name === 'winter/storm') {
                $type = 'core';
            }

            $type = $type ?? match ($details['type']) {
                'winter-plugin', 'october-plugin' => 'plugins',
                'winter-module', 'october-module' => 'modules',
                'winter-theme', 'october-theme' => 'themes',
                default => null
            };

            if (!$type) {
                continue;
            }

            $details['path'] = realpath(
                static::make()->getComposerVendorDir()
                . DIRECTORY_SEPARATOR
                . $details['install-path']
            );

            $packages[$type][$details['path']] = $details;
        }

        return $packages;
    }

    /**
     * Get the available updates for the project
     * @TODO: Check if we need to cache this
     * @return array [$package => ['from' => string, 'to' => string, 'ref' => string, 'available' => array]]
     */
    public static function getAvailableUpdates(): array
    {
        $upgrades = static::update(dryRun: true, withAllDependencies: true)->getUpgraded();
        // Get an array of package names that are winter packages
        $packages = array_values(
            array_map(
                fn ($package) => $package['name'],
                array_merge(...array_values(static::getWinterPackages()))
            )
        );

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
    }

    /**
     * Gets the latest supported version constraints for the provided package that Composer
     * would use under the current conditions
     * @TODO: Evaluate for removal if it doesn't get used for the UI
     */
    public static function getLatestSupportedVersion(string $package): string
    {
        $message = static::require(package: $package, dryRun: true);
        $output = explode(PHP_EOL, $message);
        preg_match('/Using version (.*?) /', $output[count($output) - 1], $matches);

        return $matches[1] ?? throw new CommandException('Unable to determine required version');
    }

    /**
     * Check if there is an update available for the provided package
     */
    public static function updateAvailable(string $package): bool
    {
        return isset(static::getAvailableUpdates()[$package]);
    }

    /**
     * Get the package info for the provided WinterExtension
     */
    public static function getPackageInfoByExtension(WinterExtension $extension): array
    {
        return static::getPackageInfoByPath($extension->getPath());
    }

    /**
     * Get the package name for the provided WinterExtension
     */
    public static function getPackageNameByExtension(WinterExtension $extension): ?string
    {
        return static::getPackageInfoByPath($extension->getPath())['name'];
    }

    /**
     * Get the package info from the provided path
     */
    public static function getPackageInfoByPath(string $path): array
    {
        return array_merge(...array_values(static::getWinterPackages()))[$path] ?? [];
    }

    /**
     * Get list of Winter packages that are present on the system with their current version
     * @return array [$package => ['version' => string, 'ref' => string]]
     */
    public static function getWinterPackagesWithVersion(): array
    {
        $packages = [];
        foreach (array_merge(...array_values(static::getWinterPackages())) as $package) {
            $packages[$package['name']] = [
                'version' => $package['version'] ?? null,
                'ref' => $package['dist']['reference'] ?? null
            ];
        }

        return $packages;
    }

    /**
     * Removes all dev versions not present in the keep paramater
     */
    protected static function filterProductionVersions(array $versions, array $keep = []): array
    {
        foreach ($versions as $index => $version) {
            if ((!str_starts_with($version, 'v') || str_ends_with($version, '-dev')) && !in_array($version, $keep)) {
                unset($versions[$index]);
            }
        }

        usort($versions, fn (string $a, string $b): int => version_compare($b, $a));

        return $versions;
    }
}
