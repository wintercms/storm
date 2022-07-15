<?php namespace Winter\Storm\Filesystem;

use Throwable;

/**
 * A utility to resolve paths to their canonical location and handle path queries.
 *
 * @author Ben Thomson
 */
class PathResolver
{
    /**
     * Resolves a path to its canonical location.
     *
     * This expands all symbolic links and resolves references to /./, /../ and extra / characters in the input path
     * and returns the canonicalized absolute pathname.
     *
     * This function operates very similar to the PHP `realpath` function, except it will also work for missing files
     * and directories.
     *
     * Returns canonical path if it can be resolved, otherwise `false`.
     */
    public static function resolve(string $path): string|bool
    {
        // Check if path is within any "open_basedir" restrictions
        if (!static::withinOpenBaseDir($path)) {
            return false;
        }

        // Split path into segments
        $pathSegments = explode('/', static::normalize($path));

        // Store Windows drive, if available, for final resolved path.
        $drive = array_shift($pathSegments) ?: null;

        $resolvedSegments = [];

        foreach ($pathSegments as $i => $segment) {
            // Ignore current directory markers or empty segments
            if ($segment === '' || $segment === '.') {
                continue;
            }

            // Traverse back one segment in the resolved segments
            if ($segment === '..' && count($resolvedSegments)) {
                array_pop($resolvedSegments);
                continue;
            }

            $currentPath = ($drive ?? '')
                . '/'
                . ((count($resolvedSegments))
                    ? implode('/', $resolvedSegments) . '/'
                    : '')
                . $segment;

            /**
             * We'll check to see if the current path is within "open_basedir" restrictions. Given that the full path
             * IS within the restrictions at this point - if the current path is not, we'll assume it makes up part of
             * the path and add it as a resolved segment.
             */
            if (static::withinOpenBaseDir($currentPath)) {
                try {
                    if (is_link($currentPath)) {
                        // Resolve the symlink and replace the resolved segments with the symlink's segments
                        $resolvedSymlink = static::resolveSymlink($currentPath);
                        if (!$resolvedSymlink) {
                            return false;
                        }

                        $resolvedSegments = explode('/', $resolvedSymlink);
                        $drive = array_shift($resolvedSegments) ?: null;
                        continue;
                    } elseif (is_file($currentPath) && $i < (count($pathSegments) - 1)) {
                        // If we've hit a file and we're trying to relatively traverse the path further, we need to fail at this
                        // point.
                        return false;
                    }
                } catch (Throwable $e) {
                    if (str_contains($e->getMessage(), 'open_basedir')) {
                        return false;
                    }
                }
            }

            $resolvedSegments[] = $segment;
        }

        // Generate final resolved path, removing any leftover empty segments
        return
            ($drive ?? '')
            . DIRECTORY_SEPARATOR
            . implode(DIRECTORY_SEPARATOR, array_filter($resolvedSegments, function ($item) {
                return $item !== '';
            }));
    }

    /**
     * Determines if the path is within the given directory.
     */
    public static function within(string $path, string $directory): bool
    {
        $directory = static::resolve($directory);
        $path = static::resolve($path);

        return starts_with($path, $directory);
    }

    /**
     * Join two paths, making sure they use the correct directory separators.
     */
    public static function join(string $prefix, string $path = ''): string
    {
        $fullPath = rtrim(static::normalize($prefix, false) . '/' . static::normalize($path, false), '/');

        return static::resolve($fullPath);
    }

    /**
     * Normalizes a given path.
     *
     * Converts any type of path (Unix or Windows) into a Unix-style path, so that we have a consistent format to work
     * with internally. All paths will be returned with no trailing path separator.
     *
     * If `$applyCwd` is true, the current working directory will be prepended if the path is relative.
     */
    protected static function normalize(string $path, bool $applyCwd = true): string
    {
        // Change directory separators to Unix-based
        $path = rtrim(str_replace('\\', '/', $path), '/');

        if ($applyCwd) {
            // Determine drive letter for Windows paths
            $drive = (preg_match('/^([A-Z]:)/', $path, $matches) === 1)
                ? $matches[1]
                : null;

            // Prepend current working directory for relative paths
            if (substr($path, 0, 1) !== '/' && is_null($drive)) {
                $path = static::normalize(getcwd()) . '/' . $path;
            }
        }

        return $path;
    }

    /**
     * Standardizes the path separators of a path back to the expected separator for the operating system.
     */
    public static function standardize(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, static::normalize($path, false));
    }

    /**
     * Resolves a symlink target.
     *
     * Returns the resolved symlink path, or `false` if it cannot be resolved.
     */
    protected static function resolveSymlink($symlink): string|bool
    {
        // Check that the symlink is valid and the target exists
        $stat = linkinfo($symlink);
        if ($stat === -1 || $stat === false) {
            return false;
        }

        $target = readlink($symlink);

        // If "open_basedir" restrictions are in effect, we will not allow symlinks that target outside the
        // restrictions.
        if (!$target || !static::withinOpenBaseDir($target)) {
            return false;
        }

        $targetDrive = (preg_match('/^([A-Z]:)/', $symlink, $matches) === 1)
            ? $matches[1]
            : null;

        if (substr($target, 0, 1) !== '/' && is_null($targetDrive)) {
            // Append the target in place of the symlink if it is a relative symlink
            $directory = substr($symlink, 0, strrpos($symlink, '/') + 1);
            $target = static::resolve($directory . $target);
        }

        return static::normalize($target);
    }

    /**
     * Checks if a given path is within "open_basedir" restrictions.
     */
    protected static function withinOpenBaseDir(string $path): bool
    {
        $baseDirs = ini_get('open_basedir');

        if (!$baseDirs) {
            return true;
        }

        $baseDirs = explode(PATH_SEPARATOR, $baseDirs);
        $found = false;

        foreach ($baseDirs as $baseDir) {
            if (starts_with(static::normalize($path), static::normalize($baseDir))) {
                $found = true;
                break;
            }
        }

        return $found;
    }
}
