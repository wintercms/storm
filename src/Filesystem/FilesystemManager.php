<?php namespace Winter\Storm\Filesystem;

use Illuminate\Filesystem\FilesystemManager as BaseFilesystemManager;

class FilesystemManager extends BaseFilesystemManager
{
    /**
     * Identify the provided disk and return the name of its config
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $disk
     * @return string|null Returns the disk config name if successful, null otherwise.
     */
    public function identify($disk)
    {
        $configName = null;
        foreach ($this->disks as $name => $instantiatedDisk) {
            if ($disk === $instantiatedDisk) {
                $configName = $name;
                break;
            }
        }
        return $configName;
    }

    /**
     * @inheritDoc
     */
    protected function resolve($name, $config = null)
    {
        if (is_null($config)) {
            $config = $this->getConfig($name);
        }

        // Default local drivers to public visibility for backwards compatibility
        // see https://github.com/wintercms/winter/issues/503
        if ($name === 'local' && $config['driver'] === 'local' && empty($config['visibility'])) {
            $config['visibility'] = 'public';
        }

        return parent::resolve($name, $config);
    }
}
