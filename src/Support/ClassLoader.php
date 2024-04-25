<?php namespace Winter\Storm\Support;

use Closure;
use Throwable;
use Exception;
use Winter\Storm\Filesystem\Filesystem;

/**
 * Class loader
 *
 * A simple autoloader used by Winter. Packages to be autoloaded are registered
 * via App::make(ClassLoader::class)->autoloadPackage("Namespace\Prefix",
 * "path/to/namespace"). It supports both the original October approach of all
 * lowercase folder names with proper cased filenames and the PSR-4 approach of
 * proper cased folder and filenames.
 */
class ClassLoader
{
    /**
     * The filesystem instance.
     */
    public Filesystem $files;

    /**
     * The base path.
     */
    public string $basePath;

    /**
     * The manifest path.
     */
    public ?string $manifestPath;

    /**
     * The loaded manifest array.
     */
    public ?array $manifest = null;

    /**
     * Determine if the manifest needs to be written.
     */
    protected bool $manifestDirty = false;

    /**
     * The registered packages to autoload for
     */
    protected array $autoloadedPackages = [];

    /**
     * The registered callback for loading plugins.
     */
    protected ?Closure $registered = null;

    /**
     * Class alias array.
     */
    protected array $aliases = [];

    /**
     * Namespace alias array.
     */
    protected array $namespaceAliases = [];

    /**
     * Aliases that have been explicitly loaded.
     */
    protected array $loadedAliases = [];

    /**
     * Reversed classes to ignore for alias checks.
     */
    protected array $reversedClasses = [];

    /**
     * Create a new package manifest instance.
     */
    public function __construct(Filesystem $files, string $basePath, string $manifestPath)
    {
        $this->files = $files;
        $this->basePath = $basePath;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Load the given class file.
     */
    public function load(string $class): ?bool
    {
        $class = static::normalizeClass($class);

        // If the class is already aliased, skip loading.
        if (in_array($class, $this->loadedAliases) || in_array($class, $this->reversedClasses)) {
            return true;
        }

        // Check the class manifest for the class' location
        if (
            isset($this->manifest[$class]) &&
            $this->isRealFilePath($path = $this->manifest[$class])
        ) {
            require_once $this->resolvePath($path);

            if (!is_null($reverse = $this->getReverseAlias($class))) {
                if (!class_exists($reverse, false) && !in_array($reverse, $this->loadedAliases)) {
                    class_alias($class, $reverse);
                    $this->reversedClasses[] = $reverse;
                }
            }

            return true;
        }

        // Check our registered autoload packages for a match
        foreach ($this->autoloadedPackages as $prefix => $path) {
            $lowerClass = strtolower($class);
            if (Str::startsWith($lowerClass, $prefix)) {
                $parts = explode('\\', substr($class, strlen($prefix)));
                $file = array_pop($parts) . '.php';
                $namespace = implode('\\', $parts);
                $directory = str_replace(['\\', '_'], DIRECTORY_SEPARATOR, $namespace);

                $pathsToTry = [
                    // Lowercase directory structure - default structure of plugins and modules
                    $path . strtolower($directory) . DIRECTORY_SEPARATOR . $file,

                    // Fallback to the unmodified path
                    $path . $directory . DIRECTORY_SEPARATOR . $file,
                ];

                foreach ($pathsToTry as $classPath) {
                    if ($this->isRealFilePath($classPath)) {
                        $this->includeClass($class, $classPath);
                        $reverse = $this->getReverseAlias($class);
                        if (!is_null($reverse)) {
                            if (!class_exists($reverse, false) && !in_array($reverse, $this->loadedAliases)) {
                                class_alias($class, $reverse);
                                $this->reversedClasses[] = $reverse;
                            }
                        }

                        return true;
                    }
                }
            }
        }

        if (!is_null($alias = $this->getAlias($class)) && !in_array($class, $this->reversedClasses)) {
            $this->loadedAliases[] = $class;
            class_alias($alias, $class);
            return true;
        }

        return null;
    }

    /**
     * Resolve the provided path, relative or absolute
     */
    protected function resolvePath(string $path): string
    {
        if (!$this->files->isAbsolutePath($path)) {
            $path = $this->basePath . DIRECTORY_SEPARATOR . $path;
        }
        return $path;
    }

    /**
     * Determine if the provided path to a file exists and is real
     */
    protected function isRealFilePath(string $path): bool
    {
        return is_file(realpath($this->resolvePath($path)));
    }

    /**
     * Includes a class and adds to the manifest
     */
    protected function includeClass(string $class, string $path): void
    {
        require_once $this->resolvePath($path);

        $this->manifest[$class] = $path;

        $this->manifestDirty = true;
    }

    /**
     * Register the given class loader on the auto-loader stack.
     */
    public function register(): void
    {
        if (!is_null($this->registered)) {
            return;
        }

        $this->ensureManifestIsLoaded();

        $this->registered = function ($class) {
            $this->load($class);
        };
        spl_autoload_register($this->registered);
    }

    /**
     * De-register the given class loader on the auto-loader stack.
     */
    public function unregister(): void
    {
        if (is_null($this->registered)) {
            return;
        }

        spl_autoload_unregister($this->registered);
        $this->registered = null;
    }

    /**
     * Build the manifest and write it to disk.
     */
    public function build(): void
    {
        if (!$this->manifestDirty) {
            return;
        }

        $this->write($this->manifest);
    }

    /**
     * Add a namespace prefix to the autoloader
     *
     * @param string $namespacePrefix The namespace prefix for this package
     * @param string $path The path to this package, either relative to the base path or absolute
     */
    public function autoloadPackage(string $namespacePrefix, string $path): void
    {
        // Normalize the path to an absolute path and then attempt to use the relative path
        // if the path is contained within the basePath
        $path = Str::after($this->resolvePath($path), $this->basePath . DIRECTORY_SEPARATOR);

        $this->autoloadedPackages[ltrim(Str::lower($namespacePrefix), '\\')] = $path;

        // Ensure packages are sorted by length of the prefix to prevent a greedier prefix
        // from being matched first when attempting to autoload a class
        uksort($this->autoloadedPackages, function ($a, $b) {
            return Str::substrCount($b, '\\') <=> Str::substrCount($a, '\\');
        });
    }

    /**
     * Adds alias to the class loader.
     *
     * Aliases are first-come, first-served. If a real class already exists with the same name as an alias, the real
     * class is used over the alias.
     */
    public function addAliases(array $aliases): void
    {
        foreach ($aliases as $original => $alias) {
            if (!array_key_exists($alias, $this->aliases)) {
                $this->aliases[$alias] = $original;
            }
        }
    }

    /**
     * Adds namespace aliases to the class loader.
     *
     * Similar to the "addAliases" method, but applies across an entire namespace.
     *
     * Aliases are first-come, first-served. If a real class already exists with the same name as an alias, the real
     * class is used over the alias.
     */
    public function addNamespaceAliases(array $namespaceAliases): void
    {
        foreach ($namespaceAliases as $original => $alias) {
            if (!array_key_exists($alias, $this->namespaceAliases)) {
                $alias = ltrim($alias, '\\');
                $original = ltrim($original, '\\');
                $this->namespaceAliases[$alias] = $original;
            }
        }
    }

    /**
     * Gets an alias for a class, if available.
     */
    public function getAlias(string $class): ?string
    {
        if (count($this->namespaceAliases)) {
            foreach ($this->namespaceAliases as $alias => $original) {
                if (starts_with($class, $alias)) {
                    return str_replace($alias, $original, $class);
                }
            }
        }

        return array_key_exists($class, $this->aliases)
            ? $this->aliases[$class]
            : null;
    }

    /**
     * Gets aliases registered for a namespace, if available.
     */
    public function getNamespaceAliases(string $namespace): array
    {
        $aliases = [];
        foreach ($this->namespaceAliases as $alias => $original) {
            if ($namespace === $original) {
                $aliases[] = $alias;
            }
        }

        return $aliases;
    }

    /**
     * Gets a reverse alias for a class, if available.
     */
    public function getReverseAlias(string $class): ?string
    {
        if (count($this->namespaceAliases)) {
            foreach ($this->namespaceAliases as $alias => $original) {
                if (starts_with($class, $original)) {
                    return str_replace($original, $alias, $class);
                }
            }
        }

        $aliasKey = array_search($class, $this->aliases);

        return ($aliasKey !== false)
            ? $aliasKey
            : null;
    }

    /**
     * Normalise the class name.
     */
    protected static function normalizeClass(string $class): string
    {
        /*
         * Strip first slash
         */
        if (substr($class, 0, 1) == '\\') {
            $class = substr($class, 1);
        }

        return implode('\\', array_map(function ($part) {
            return Str::studly($part);
        }, explode('\\', $class)));
    }

    /**
     * Ensure the manifest has been loaded into memory.
     */
    protected function ensureManifestIsLoaded(): void
    {
        if (!is_null($this->manifest)) {
            return;
        }

        if (file_exists($this->manifestPath)) {
            try {
                $this->manifest = $this->files->getRequire($this->manifestPath);

                if (!is_array($this->manifest)) {
                    $this->manifest = [];
                }
            } catch (Exception $ex) {
                $this->manifest = [];
            } catch (Throwable $ex) {
                $this->manifest = [];
            }
        } else {
            $this->manifest = [];
        }
    }

    /**
     * Write the given manifest array to disk.
     *
     * @throws \Exception if the manifest path is not writable
     */
    protected function write(array $manifest): void
    {
        if (!is_writable(dirname($this->manifestPath))) {
            throw new Exception('The storage/framework/cache directory must be present and writable.');
        }

        $this->files->put(
            $this->manifestPath,
            '<?php return '.var_export($manifest, true).';'
        );
    }
}
