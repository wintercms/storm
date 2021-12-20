<?php namespace Winter\Storm\Filesystem;

/**
 * Zip helper
 *
 * @author Alexey Bobkov, Samuel Georges
 *
 * Usage:
 *
 *   Zip::make('file.zip', '/some/path/*.php');
 *
 *   Zip::make('file.zip', function($zip) {
 *
 *       // Add all PHP files and directories
 *       $zip->add('/some/path/*.php');
 *
 *       // Do not include subdirectories, one level only
 *       $zip->add('/non/recursive/*', ['recursive' => false]);
 *
 *       // Add multiple paths
 *       $zip->add([
 *           '/collection/of/paths/*',
 *           '/a/single/file.php'
 *       ]);
 *
 *       // Add all INI files to a zip folder "config"
 *       $zip->folder('/config', '/path/to/config/*.ini');
 *
 *       // Add multiple paths to a zip folder "images"
 *       $zip->folder('/images', function($zip) {
 *           $zip->add('/my/gifs/*.gif', );
 *           $zip->add('/photo/reel/*.{png,jpg}', );
 *       });
 *
 *       // Remove these files/folders from the zip
 *       $zip->remove([
 *           '.htaccess',
 *           'config.php',
 *           'some/folder'
 *       ]);
 *
 *   });
 *
 *   Zip::extract('file.zip', '/destination/path');
 *
 */

use ZipArchive;

class Zip extends ZipArchive
{
    /**
     * Folder prefix
     */
    protected string $folderPrefix = '';

    /**
     * Lock down the constructor for this class.
     */
    final public function __construct()
    {
    }

    /**
     * Extracts an existing ZIP file.
     */
    public static function extract(string $source, string $destination, array $options = []): bool
    {
        $mask = $options['mask'] ?? 0777;

        if (file_exists($destination) || mkdir($destination, $mask, true)) {
            $zip = new ZipArchive;
            if ($zip->open($source) === true) {
                $zip->extractTo($destination);
                $zip->close();
                return true;
            }
        }

        return false;
    }

    /**
     * Creates a new empty Zip file, optionally populating it with given source files.
     *
     * Source can be a single path, an array of paths or a callback which allows you to manipulate the Zip file.
     */
    public static function make(string $destination, string|callable|array|null $source = null, array $options = []): static
    {
        $zip = new static;
        $zip->open($destination, ZIPARCHIVE::CREATE | ZipArchive::OVERWRITE);

        if (is_string($source)) {
            $zip->add($source, $options);
        } elseif (is_callable($source)) {
            $source($zip);
        } elseif (is_array($source)) {
            foreach ($source as $_source) {
                $zip->add($_source, $options);
            }
        }

        $zip->close();
        return $zip;
    }

    /**
     * Adds a source file or directory to a Zip file.
     */
    public function add(string $source, array $options = []): self
    {
        $recursive = (bool) ($options['recursive'] ?? true);
        $includeHidden = isset($options['includeHidden']) && $options['includeHidden'] === true;

        /*
         * A directory has been supplied, convert it to a useful glob
         *
         * The wildcard for including hidden files:
         * - isn't hidden with an '.'
         * - is hidden with a '.' but is followed by a non '.' character
         * - starts with '..' but has at least one character after it
         */
        if (is_dir($source)) {
            $wildcard = $includeHidden ? '{*,.[!.]*,..?*}' : '*';
            $source = implode('/', [dirname($source), basename($source), $wildcard]);
        }

        $basedir = dirname($source);
        $baseglob = basename($source);

        if (is_file($source)) {
            $files = [$source];
            $folders = [];
            $recursive = false;
        } else {
            $files = glob($source, GLOB_BRACE);
            $folders = glob(dirname($source) . '/*', GLOB_ONLYDIR);
        }

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $localpath = $this->removePathPrefix($basedir.'/', dirname($file).'/');
            $localfile = $this->folderPrefix . $localpath . basename($file);
            $this->addFile($file, $localfile);
        }

        if (!$recursive) {
            return $this;
        }

        foreach ($folders as $folder) {
            if (!is_dir($folder)) {
                continue;
            }

            $localpath = $this->folderPrefix . $this->removePathPrefix($basedir.'/', $folder.'/');
            $this->addEmptyDir($localpath);
            $this->add($folder.'/'.$baseglob, array_merge($options, ['basedir' => $basedir]));
        }

        return $this;
    }

    /**
     * Creates a new folder inside the Zip file, and optionally adds the given source files/folders to this folder.
     *
     * Source can be a single path, an array of paths or a callback which allows you to manipulate the Zip file.
     */
    public function folder(string $name, string|callable|array|null $source = null): self
    {
        $prefix = $this->folderPrefix;
        $this->addEmptyDir($prefix . $name);
        if ($source === null) {
            return $this;
        }

        $this->folderPrefix = $prefix . $name . '/';

        if (is_string($source)) {
            $this->add($source);
        } elseif (is_callable($source)) {
            $source($this);
        } elseif (is_array($source)) {
            foreach ($source as $_source) {
                $this->add($_source);
            }
        }

        $this->folderPrefix = $prefix;
        return $this;
    }

    /**
     * Removes file(s) or folder(s) from the Zip file.
     *
     * Does not support wildcards.
     */
    public function remove(array|string $source): self
    {
        if (is_array($source)) {
            foreach ($source as $_source) {
                $this->remove($_source);
            }
        }

        if (!is_string($source)) {
            return $this;
        }

        if (substr($source, 0, 1) == '/') {
            $source = substr($source, 1);
        }

        for ($i = 0; $i < $this->numFiles; $i++) {
            $stats = $this->statIndex($i);
            if (substr($stats['name'], 0, strlen($source)) == $source) {
                $this->deleteIndex($i);
            }
        }

        return $this;
    }

    /**
     * Removes a prefix from a given path.
     */
    protected function removePathPrefix(string $prefix, string $path): string
    {
        return (strpos($path, $prefix) === 0)
            ? substr($path, strlen($prefix))
            : $path;
    }
}
