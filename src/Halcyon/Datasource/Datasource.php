<?php namespace Winter\Storm\Halcyon\Datasource;

/**
 * Datasource base class.
 */
abstract class Datasource implements DatasourceInterface
{
    use \Winter\Storm\Support\Traits\Emitter;

    /**
     * @var bool Indicates if the record is currently being force deleted.
     */
    protected $forceDeleting = false;

    /**
     * The query post processor implementation.
     *
     * @var \Winter\Storm\Halcyon\Processors\Processor
     */
    protected $postProcessor;

    /**
     * @inheritDoc
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * @inheritDoc
     */
    abstract public function selectOne(string $dirName, string $fileName, string $extension);

    /**
     * @inheritDoc
     */
    abstract public function select(string $dirName, array $options = []);

    /**
     * @inheritDoc
     */
    abstract public function insert(string $dirName, string $fileName, string $extension, string $content);

    /**
     * @inheritDoc
     */
    abstract public function update(string $dirName, string $fileName, string $extension, string $content, $oldFileName = null, $oldExtension = null);

    /**
     * @inheritDoc
     */
    abstract public function delete(string $dirName, string $fileName, string $extension);

    /**
     * @inheritDoc
     */
    public function forceDelete(string $dirName, string $fileName, string $extension)
    {
        $this->forceDeleting = true;

        $success = $this->delete($dirName, $fileName, $extension);

        $this->forceDeleting = false;

        return $success;
    }

    /**
     * @inheritDoc
     */
    abstract public function lastModified(string $dirName, string $fileName, string $extension);

    /**
     * @inheritDoc
     */
    public function makeCacheKey($name = '')
    {
        return (string) crc32($name);
    }

    /**
     * @inheritDoc
     */
    abstract public function getPathsCacheKey();

    /**
     * @inheritDoc
     */
    abstract public function getAvailablePaths();
}
