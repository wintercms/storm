<?php namespace Winter\Storm\Halcyon\Datasource;

use \Winter\Storm\Halcyon\Processors\Processor;

/**
 * Datasource base class.
 */
abstract class Datasource implements DatasourceInterface
{
    use \Winter\Storm\Support\Traits\Emitter;

    /**
     * Indicates if the record is currently being force deleted.
     */
    protected bool $forceDeleting = false;

    /**
     * The query post processor implementation.
     */
    protected Processor $postProcessor;

    /**
     * @inheritDoc
     */
    public function getPostProcessor(): Processor
    {
        return $this->postProcessor;
    }

    /**
     * @inheritDoc
     */
    abstract public function selectOne(string $dirName, string $fileName, string $extension): ?array;

    /**
     * @inheritDoc
     */
    abstract public function select(string $dirName, array $options = []): array;

    /**
     * @inheritDoc
     */
    abstract public function insert(string $dirName, string $fileName, string $extension, string $content): int;

    /**
     * @inheritDoc
     */
    abstract public function update(string $dirName, string $fileName, string $extension, string $content, ?string $oldFileName = null, ?string $oldExtension = null): int;

    /**
     * @inheritDoc
     */
    abstract public function delete(string $dirName, string $fileName, string $extension): bool;

    /**
     * @inheritDoc
     */
    public function forceDelete(string $dirName, string $fileName, string $extension): bool
    {
        $this->forceDeleting = true;

        try {
            return $this->delete($dirName, $fileName, $extension);
        } finally {
            // A delete that throws must not leave the datasource in force-deleting mode,
            // or every later ordinary delete on this instance becomes a hard delete.
            $this->forceDeleting = false;
        }
    }

    /**
     * @inheritDoc
     */
    abstract public function lastModified(string $dirName, string $fileName, string $extension): ?int;

    /**
     * @inheritDoc
     */
    public function makeCacheKey(string $name = ''): string
    {
        return hash('crc32b', $name);
    }

    /**
     * @inheritDoc
     */
    abstract public function getPathsCacheKey(): string;

    /**
     * @inheritDoc
     */
    abstract public function getAvailablePaths(): array;
}
