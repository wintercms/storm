<?php namespace Winter\Storm\Translation;

use Illuminate\Translation\FileLoader as FileLoaderBase;

class FileLoader extends FileLoaderBase
{
    /**
     * Load a local namespaced translation group for overrides.
     *
     * This is an override from the base Laravel functionality that allows "xx-xx" locale format
     * files as well as "xx_XX" locale format files. The "xx_XX" format is considered authorative.
     *
     * @param  array  $lines
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
    {
        $namespace = str_replace('.', '/', $namespace);

        $file = "{$this->path}/{$locale}/{$namespace}/{$group}.php";

        if ($this->files->exists($file)) {
            return array_replace_recursive($lines, $this->files->getRequire($file));
        }

        // Try "xx-xx" format
        $locale = str_replace('_', '-', strtolower($locale));

        if ("{$this->path}/{$locale}/{$namespace}/{$group}.php" !== $file) {
            $file = "{$this->path}/{$locale}/{$namespace}/{$group}.php";

            if ($this->files->exists($file)) {
                return array_replace_recursive($lines, $this->files->getRequire($file));
            }
        }

        return $lines;
    }

    /**
     * Load a locale from a given path.
     *
     * This is an override from the base Laravel functionality that allows "xx-xx" locale format
     * files as well as "xx_XX" locale format files. The "xx_XX" format is considered authorative.
     *
     * @param  string  $path
     * @param  string  $locale
     * @param  string  $group
     * @return array
     */
    protected function loadPath($path, $locale, $group)
    {
        if ($this->files->exists($full = "{$path}/{$locale}/{$group}.php")) {
            return $this->files->getRequire($full);
        }

        // Try "xx-xx" format
        $locale = str_replace('_', '-', strtolower($locale));

        if ("{$path}/{$locale}/{$group}.php" !== $full) {
            if ($this->files->exists($full = "{$path}/{$locale}/{$group}.php")) {
                return $this->files->getRequire($full);
            }
        }

        return [];
    }
}
