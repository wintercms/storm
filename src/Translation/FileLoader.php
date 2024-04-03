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
        return collect($this->paths)
            ->reduce(function ($output, $path) use ($lines, $locale, $group, $namespace) {
                $winterNamespace = str_replace('.', '/', $namespace);

                // Look for a Winter-managed namespace
                $file = "{$path}/{$locale}/{$winterNamespace}/{$group}.php";
                if ($this->files->exists($file)) {
                    return array_replace_recursive($lines, $this->files->getRequire($file));
                }

                // Look for a Winter-managed namespace with a Winter-formatted locale (xx-xx instead of xx_XX)
                $dashLocale = str_replace('_', '-', strtolower($locale));
                $dashFile = "{$path}/{$dashLocale}/{$winterNamespace}/{$group}.php";
                if ($dashFile !== $file && $this->files->exists($dashFile)) {
                    return array_replace_recursive($lines, $this->files->getRequire($dashFile));
                }

                // Look for a vendor-managed namespace
                $file = "{$path}/vendor/{$namespace}/{$locale}/{$group}.php";
                if ($this->files->exists($file)) {
                    return array_replace_recursive($lines, $this->files->getRequire($file));
                }

                return $lines;
            }, []);
    }

    /**
     * Load a locale from a given path.
     *
     * This is an override from the base Laravel functionality that allows "xx-xx" locale format
     * files as well as "xx_XX" locale format files. The "xx_XX" format is considered authorative.
     *
     * @param  array  $paths
     * @param  string  $locale
     * @param  string  $group
     * @return array
     */
    protected function loadPaths(array $paths, $locale, $group)
    {
        return collect($paths)
            ->reduce(function ($output, $path) use ($locale, $group) {
                $file = "{$path}/{$locale}/{$group}.php";
                if ($this->files->exists($file)) {
                    return array_replace_recursive($output, $this->files->getRequire($file));
                }

                // Try "xx-xx" format
                $dashLocale = str_replace('_', '-', strtolower($locale));
                $dashFile = "{$path}/{$dashLocale}/{$group}.php";
                if ($dashFile !== $file && $this->files->exists($dashFile)) {
                    return $this->files->getRequire($dashFile);
                }

                return $output;
            }, []);
    }
}
