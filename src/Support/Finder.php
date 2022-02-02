<?php

namespace Winter\Storm\Support;

use FilesystemIterator as FI;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RegexIterator;
use Winter\Storm\Exception\SystemException;

class Finder
{
    const DS = DIRECTORY_SEPARATOR;

    protected $basePath = null;

    protected $files = [];
    protected $paths = [];
    protected $ignore = [];
    protected $match = [];

    protected $iteratorFlags = FI::KEY_AS_PATHNAME | FI::CURRENT_AS_FILEINFO | FI::SKIP_DOTS;

    public function __construct(?string $basePath = null)
    {
        $this->setBasePath($basePath);
    }

    public static function create(?string $basePath = null): Finder
    {
        return new static($basePath ?? base_path());
    }

    public function setBasePath(string $path): Finder
    {
        $this->basePath = rtrim($path, '/') . '/';

        return $this;
    }

    public function path($path): Finder
    {
        if (is_array($path)) {
            foreach ($path as $p) {
                $this->path($p);
            }
            return $this;
        }

        $path = ($this->basePath ?? '') . $path;

        if (str_contains($path, '*')) {
            $this->paths = array_merge($this->paths, $this->expandPath($path));
            return $this;
        }

        if (is_file($path)) {
            $this->files[] = $path;
            return $this;
        }

        if (!file_exists($path)) {
            return $this;
        }

        $this->paths[] = $path;
        return $this;
    }

    protected function expandPath(string $path): array
    {
        if (!is_file($path)) {
            $this->files[] = $path;
            return [];
        }

        list($start, $end) = explode('*', $path, 2);

        $dirs = [];

        if (!is_dir($start)) {
            return [];
        }

        foreach (array_diff(scandir($start), ['.', '..']) as $dir) {
            $dir = rtrim($start, static::DS) . static::DS . $dir;

            if (str_contains($end, '*')) {
                $dirs = array_merge($dirs,
                    $this->expandPath(rtrim($dir, static::DS) . static::DS . ltrim($end, static::DS))
                );
                continue;
            }

            $dir = $dir . $end;

            if (!is_dir($dir)) {
                continue;
            }

            $dirs[] = $dir;
        }

        return $dirs;
    }

    public function ignore($path): Finder
    {
        if (is_array($path)) {
            foreach ($path as $p) {
                $this->ignore($p);
            }

            return $this;
        }

        $this->ignore[] = $path;

        return $this;
    }

    public function match($patterns): Finder
    {
        if (is_array($patterns)) {
            foreach ($patterns as $p) {
                $this->match($p);
            }

            return $this;
        }

        $this->match[] = $patterns;


        return $this;
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function scan(): array
    {
        $files = $this->files;

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RegexIterator(
                $this->getRecursiveIterator($path),
                '/.+(\.(' . implode('|', $this->match) . ')$)/i',
                RegexIterator::GET_MATCH
            );

            foreach ($iterator as $path => $file) {
                foreach ($this->ignore as $ignore) {
                    if (preg_match($ignore, $path)) {
                        continue 2;
                    }
                }
                $files[] = $path;
            }
        }

        return $files;
    }

    protected function getRecursiveIterator(string $path): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            $this->getDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST
        );
    }

    protected function getDirectoryIterator(string $path): RecursiveDirectoryIterator
    {
        return new RecursiveDirectoryIterator($path, $this->iteratorFlags);
    }
}
