<?php namespace Winter\Storm\Parse\Assetic\Cache;

use RuntimeException;
use Assetic\Cache\FilesystemCache as BaseFilesystemCache;
use Winter\Storm\Support\Facades\File;

/**
 * Assetic Filesystem Cache
 * Inherits the base logic except new files have permissions set.
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class FilesystemCache extends BaseFilesystemCache
{
    public function set($key, $value)
    {
        if (!is_dir($this->dir) && false === @mkdir($this->dir, 0777, true)) {
            throw new RuntimeException('Unable to create directory '.$this->dir);
        }

        $path = $this->dir.'/'.$key;

        if (false === @file_put_contents($path, $value)) {
            throw new RuntimeException('Unable to write file '.$path);
        }

        File::chmod($path);
    }
}
