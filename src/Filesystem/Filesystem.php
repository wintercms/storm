<?php namespace Winter\Storm\Filesystem;

use DirectoryIterator;
use FilesystemIterator;
use ReflectionClass;
use Winter\Storm\Support\Facades\Config;
use Illuminate\Filesystem\Filesystem as FilesystemBase;

/**
 * File helper
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class Filesystem extends FilesystemBase
{
    /**
     * Default file permission mask as a string ("777").
     */
    public ?string $filePermissions = null;

    /**
     * Default folder permission mask as a string ("777").
     */
    public ?string $folderPermissions = null;

    /**
     * Known path symbols and their prefixes.
     */
    public array $pathSymbols = [];

    /**
     * Symlinks within base folder
     */
    protected ?array $symlinks = null;

    /**
     * Determine if the given path contains no files.
     *
     * Returns a boolean regarding if the directory is empty or not. If the directory does not exist or is not
     * readable, this method will return `null`.
     */
    public function isDirectoryEmpty(string $directory): ?bool
    {
        if (!is_readable($directory)) {
            return null;
        }

        $handle = opendir($directory);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..') {
                closedir($handle);
                return false;
            }
        }

        closedir($handle);
        return true;
    }

    /**
     * Converts a file size in bytes to a human readable format.
     */
    public function sizeToString(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        if ($bytes > 1) {
            return $bytes . ' bytes';
        }

        if ($bytes == 1) {
            return $bytes . ' byte';
        }

        return '0 bytes';
    }

    /**
     * Returns a public file path from an absolute path.
     *
     * Eg: `/home/mysite/public_html/welcome` -> `/welcome`
     *
     * Returns `null` if the path cannot be converted.
     */
    public function localToPublic(string $path): ?string
    {
        $result = null;
        $publicPath = public_path();

        if (strpos($path, $publicPath) === 0) {
            $result = str_replace("\\", "/", substr($path, strlen($publicPath)));
        } else {
            /**
             * Find symlinks within base folder and work out if this path can be resolved to a symlinked directory.
             *
             * This abides by the `cms.restrictBaseDir` config and will not allow symlinks to external directories
             * if the restriction is enabled.
             */
            if ($this->symlinks === null) {
                $this->findSymlinks();
            }
            if (count($this->symlinks) > 0) {
                foreach ($this->symlinks as $source => $target) {
                    if (strpos($path, $target) === 0) {
                        $relativePath = substr($path, strlen($target));
                        $result = str_replace("\\", "/", substr($source, strlen($publicPath)) . $relativePath);
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Determines if the given path is a local path.
     *
     * Returns `true` if the path is local, `false` otherwise.
     *
     * @param string $path The path to check
     * @param boolean $realpath If `true` (default), the `realpath()` method will be used to resolve symlinks before checking if
     *  the path is local. Set to `false` if you are looking up non-existent paths.
     */
    public function isLocalPath(string $path, bool $realpath = true): bool
    {
        $base = base_path();

        if ($realpath) {
            $path = realpath($path);
        }

        return !($path === false || strncmp($path, $base, strlen($base)) !== 0);
    }

    /**
     * Determines if the given disk is using the "local" driver.
     */
    public function isLocalDisk(\Illuminate\Filesystem\FilesystemAdapter $disk): bool
    {
        return ($disk->getAdapter() instanceof \League\Flysystem\Local\LocalFilesystemAdapter);
    }

    /**
     * Finds the path of a given class.
     *
     * Returns `false` if the path cannot be determined.
     *
     * @param string|object $className Class name or object
     */
    public function fromClass(string|object $className): string|false
    {
        $reflector = new ReflectionClass($className);
        return $reflector->getFileName();
    }

    /**
     * Determines if a file exists (ignoring the case for the filename only).
     */
    public function existsInsensitive(string $path): string|false
    {
        if ($this->exists($path)) {
            return $path;
        }

        $directoryName = dirname($path);
        $pathLower = strtolower($path);

        if (!$files = $this->glob($directoryName . '/*', GLOB_NOSORT)) {
            return false;
        }

        foreach ($files as $file) {
            if (strtolower($file) == $pathLower) {
                return $file;
            }
        }

        return false;
    }

    /**
     * Normalizes the directory separator, often used by Windows systems.
     */
    public function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Converts a path using path symbol.
     *
     * Returns the original path if no symbol is used, and no default is specified.
     */
    public function symbolizePath(string $path, string|bool|null $default = null): string
    {
        if (!$this->isPathSymbol($path)) {
            return (is_null($default)) ? $path : $default;
        }

        $firstChar = substr($path, 0, 1);
        $_path = substr($path, 1);
        return $this->pathSymbols[$firstChar] . $_path;
    }

    /**
     * Determines if the given path is using a path symbol.
     */
    public function isPathSymbol(string $path): bool
    {
        return array_key_exists(substr($path, 0, 1), $this->pathSymbols);
    }

    /**
     * Write the contents of a file.
     *
     * This method will also set the permissions based on the given chmod() mask in use.
     *
     * Returns the number of bytes written to the file, or `false` on failure.
     *
     * @param string $path
     * @param string $contents
     * @param bool|int $lock
     * @return bool|int
     */
    public function put($path, $contents, $lock = false)
    {
        $result = parent::put($path, $contents, $lock);
        $this->chmod($path);
        return $result;
    }

    /**
     * Copy a file to a new location.
     *
     * This method will also set the permissions based on the given chmod() mask in use.
     *
     * Returns `true` if successful, or `false` on failure.
     *
     * @param string $path
     * @param string $target
     * @return bool
     */
    public function copy($path, $target)
    {
        $result = parent::copy($path, $target);
        $this->chmod($target);
        return $result;
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @param bool $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0777, $recursive = false, $force = false)
    {
        $mask = $this->getFolderPermissions();
        if (!is_null($mask)) {
            $mode = $mask;
        }

        /*
         * Find the green leaves
         */
        if ($recursive === true && !is_null($mask)) {
            $chmodPath = $path;
            while (true) {
                $basePath = dirname($chmodPath);
                if ($chmodPath === $basePath) {
                    break;
                }
                if ($this->isDirectory($basePath)) {
                    break;
                }
                $chmodPath = $basePath;
            }
        } else {
            $chmodPath = $path;
        }

        /*
         * Make the directory
         */
        $result = parent::makeDirectory($path, $mode, $recursive, $force);

        /*
         * Apply the permissions
         */
        if ($mask) {
            $this->chmod($chmodPath, $mask);

            if ($recursive) {
                $this->chmodRecursive($chmodPath, null, $mask);
            }
        }

        return $result;
    }

    /**
     * Modify file/folder permissions.
     *
     * @param string $path
     * @param int|float|null $mask
     * @return bool
     */
    public function chmod($path, $mask = null)
    {
        if (!$mask) {
            $mask = $this->isDirectory($path)
                ? $this->getFolderPermissions()
                : $this->getFilePermissions();
        }

        if (!$mask) {
            return false;
        }

        return @chmod($path, $mask);
    }

    /**
     * Modify file/folder permissions recursively in a given path.
     */
    public function chmodRecursive(string $path, int|float|null $fileMask = null, int|float|null $directoryMask = null): void
    {
        if (!$fileMask) {
            $fileMask = $this->getFilePermissions();
        }

        if (!$directoryMask) {
            $directoryMask = $this->getFolderPermissions() ?: $fileMask;
        }

        if (!$fileMask) {
            return;
        }

        if (!$this->isDirectory($path)) {
            $this->chmod($path, $fileMask);
            return;
        }

        $items = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $_path = $item->getPathname();
                $this->chmod($_path, $directoryMask);
                $this->chmodRecursive($_path, $fileMask, $directoryMask);
            }
            else {
                $this->chmod($item->getPathname(), $fileMask);
            }
        }
    }

    /**
     * Returns the default file permission mask to use.
     */
    public function getFilePermissions(): int|float|null
    {
        return $this->filePermissions
            ? octdec($this->filePermissions)
            : null;
    }

    /**
     * Returns the default folder permission mask to use.
     */
    public function getFolderPermissions(): int|float|null
    {
        return $this->folderPermissions
            ? octdec($this->folderPermissions)
            : null;
    }

    /**
     * Match filename against a pattern.
     */
    public function fileNameMatch(string $fileName, string $pattern): bool
    {
        if ($pattern === $fileName) {
            return true;
        }

        $regex = strtr(preg_quote($pattern, '#'), ['\*' => '.*', '\?' => '.']);

        return (bool) preg_match('#^' . $regex . '$#i', $fileName);
    }

    /**
     * Finds symlinks within the base path and populates the local symlinks property with an array of source => target symlinks.
     */
    protected function findSymlinks(): void
    {
        $restrictBaseDir = Config::get('cms.restrictBaseDir', true);
        $deep = Config::get('develop.allowDeepSymlinks', false);
        $basePath = base_path();
        $symlinks = [];

        $iterator = function ($path) use (&$iterator, &$symlinks, $basePath, $restrictBaseDir, $deep) {
            foreach (new DirectoryIterator($path) as $directory) {
                if (
                    $directory->isDir() === false
                    || $directory->isDot() === true
                ) {
                    continue;
                }
                if ($directory->isLink()) {
                    $source = $directory->getPathname();
                    $target = realpath(readlink($directory->getPathname()));
                    if (!$target) {
                        $target = realpath($directory->getPath() . '/' . readlink($directory->getPathname()));

                        if (!$target) {
                            // Cannot resolve symlink
                            continue;
                        }
                    }

                    if ($restrictBaseDir && strpos($target . '/', $basePath . '/') !== 0) {
                        continue;
                    }
                    $symlinks[$source] = $target;
                    continue;
                }

                // Get subfolders if "develop.allowDeepSymlinks" is enabled.
                if ($deep) {
                    $iterator($directory->getPathname());
                }
            }
        };
        $iterator($basePath);

        $this->symlinks = $symlinks;
    }
}
