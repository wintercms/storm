<?php namespace Winter\Storm\Support;

use Winter\Storm\Filesystem\Filesystem;
use Throwable;
use Exception;

/**
 * Class loader
 *
 * A simple autoloader used by Winter, it expects the folder names
 * to be lower case and the file name to be capitalized as per the class name.
 */
class ClassLoader
{
    /**
     * @var \Winter\Storm\Filesystem\Filesystem The filesystem instance.
     */
    public $files;

    /**
     * @var string The base path.
     */
    public $basePath;

    /**
     * @var string|null The manifest path.
     */
    public $manifestPath;

    /**
     * @var array The loaded manifest array.
     */
    public $manifest;

    /**
     * @var bool Determine if the manifest needs to be written.
     */
    protected $manifestDirty = false;

    /**
     * @var array The registered packages to autoload for
     */
    protected $autoloadedPackages = [];

    /**
     * @var bool Indicates if a ClassLoader has been registered.
     */
    protected $registered = false;

    /**
     * @var array Class alias array.
     */
    protected $aliases = [];

    /**
     * @var array Namespace alias array.
     */
    protected $namespaceAliases = [];

    /**
     * @var array Aliases that have been explicitly loaded.
     */
    protected $loadedAliases = [];

    /**
     * @var array Reversed classes to ignore for alias checks.
     */
    protected $reversedClasses = [];

    /**
     * Create a new package manifest instance.
     *
     * @param  \Winter\Storm\Filesystem\Filesystem  $files
     * @param  string  $basePath
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(Filesystem $files, $basePath, $manifestPath)
    {
        $this->files = $files;
        $this->basePath = $basePath;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Load the given class file.
     *
     * @param  string  $class
     * @return bool|null
     */
    public function load($class)
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
            if (Str::startsWith($class, $prefix)) {
                $parts = explode('\\', Str::after($class, $prefix));
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

                        if (!is_null($reverse = $this->getReverseAlias($class))) {
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
    }

    /**
     * Resolve the provided path, relative or absolute
     */
    protected function resolvePath(string $path): string
    {
        if (!Str::startsWith($path, ['/', '\\'])) {
            $path = $this->basePath . DIRECTORY_SEPARATOR . $path;
        }
        return $path;
    }

    /**
     * Determine if the provided path to a file exists and is real
     *
     * @param  string  $path
     * @return bool
     */
    protected function isRealFilePath($path)
    {
        return is_file(realpath($this->resolvePath($path)));
    }

    /**
     * Includes a class and adds to the manifest
     *
     * @param  string  $class
     * @param  string  $path
     * @return void
     */
    protected function includeClass($class, $path)
    {
        require_once $this->resolvePath($path);

        $this->manifest[$class] = $path;

        $this->manifestDirty = true;
    }

    /**
     * Register the given class loader on the auto-loader stack.
     *
     * @return void
     */
    public function register()
    {
        if ($this->registered) {
            return;
        }

        $this->ensureManifestIsLoaded();

        $this->registered = spl_autoload_register([$this, 'load']);
    }

    /**
     * De-register the given class loader on the auto-loader stack.
     *
     * @return void
     */
    public function unregister()
    {
        if (!$this->registered) {
            return;
        }

        spl_autoload_unregister([$this, 'load']);
        $this->registered = false;
    }

    /**
     * Build the manifest and write it to disk.
     *
     * @return void
     */
    public function build()
    {
        if (!$this->manifestDirty) {
            return;
        }

        $this->write($this->manifest);
    }

    /**
     * Add a namespace prefix to the autoloader
     */
    public function autoloadPackage(string $namespacePrefix, string $relativePath): void
    {
        $this->autoloadedPackages[$namespacePrefix] = $relativePath;
    }

    /**
     * Adds alias to the class loader.
     *
     * Aliases are first-come, first-served. If a real class already exists with the same name as an alias, the real
     * class is used over the alias.
     *
     * @param array $aliases
     * @return void
     */
    public function addAliases(array $aliases)
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
     *
     * @param array $aliases
     * @return void
     */
    public function addNamespaceAliases(array $namespaceAliases)
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
     *
     * @param string $class
     * @return string|null
     */
    public function getAlias($class)
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
     *
     * @param string $namespace
     * @return array
     */
    public function getNamespaceAliases($namespace)
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
     *
     * @param string $class
     * @return string|null
     */
    public function getReverseAlias($class)
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
     *
     * @param string $class
     * @return string
     */
    protected static function normalizeClass($class)
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
     * Get the possible paths for a class.
     *
     * @param  string  $class
     * @return string
     */
    protected static function getPathsForClass($class)
    {
        /*
         * Lowercase folders
         */
        $parts = explode('\\', $class);
        $file = array_pop($parts);
        $namespace = implode('\\', $parts);
        $directory = str_replace(['\\', '_'], DIRECTORY_SEPARATOR, $namespace);

        /*
         * Provide both alternatives
         */
        $lowerClass = strtolower($directory) . DIRECTORY_SEPARATOR . $file . '.php';
        $upperClass = $directory . DIRECTORY_SEPARATOR . $file . '.php';

        $lowerClassStudlyFile = strtolower($directory) . DIRECTORY_SEPARATOR . Str::studly($file) . '.php';
        $upperClassStudlyFile = $directory . DIRECTORY_SEPARATOR . Str::studly($file) . '.php';

        return [$lowerClass, $upperClass, $lowerClassStudlyFile, $upperClassStudlyFile];
    }

    /**
     * Ensure the manifest has been loaded into memory.
     *
     * @return void
     */
    protected function ensureManifestIsLoaded()
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
            }
            catch (Exception $ex) {
                $this->manifest = [];
            }
            catch (Throwable $ex) {
                $this->manifest = [];
            }
        }
        else {
            $this->manifest = [];
        }
    }

    /**
     * Write the given manifest array to disk.
     *
     * @param  array  $manifest
     * @return void
     * @throws \Exception
     */
    protected function write(array $manifest)
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
