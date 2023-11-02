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

        foreach ($this->paths as $path) {
            $file = "{$path}/{$locale}/{$namespace}/{$group}.php";

            if ($this->files->exists($file)) {
                return array_replace_recursive($lines, $this->files->getRequire($file));
            }

            // Try "xx-xx" format
            $locale = str_replace('_', '-', strtolower($locale));

            if ("{$path}/{$locale}/{$namespace}/{$group}.php" !== $file) {
                $file = "{$path}/{$locale}/{$namespace}/{$group}.php";

                if ($this->files->exists($file)) {
                    return array_replace_recursive($lines, $this->files->getRequire($file));
                }
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
     * @param  array<string>  $paths
     * @param  string  $locale
     * @param  string  $group
     * @return array
     */
    protected function loadPaths($paths, $locale, $group)
    {
        return collect($paths)
            ->reduce(function ($output, $path) use ($locale, $group) {
                $loc = str_replace('_', '-', strtolower($locale));
                $full1 = "{$path}/{$locale}/{$group}.php";
                foreach ($loc === $locale ? [$full1] : [$full1, "{$path}/{$loc}/{$group}.php"] as $full) {
                    if ($this->files->exists($full)) {
                        $output = array_replace_recursive($output, $this->files->getRequire($full));
                        break;
                    }
                }

                return $output;
            }, []);
    }
}
