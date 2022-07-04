<?php namespace Winter\Storm\Halcyon\Datasource;

interface DatasourceInterface
{
    /**
     * Get the query post processor used by the connection.
     */
    public function getPostProcessor(): \Winter\Storm\Halcyon\Processors\Processor;

    /**
     * Returns a single Halcyon model (template).
     *
     * @param string $dirName The directory in which the model is stored.
     * @param string $fileName The filename of the model.
     * @param string $extension The file extension of the model.
     * @return array|null An array of template data (`fileName`, `mtime` and `content`), or `null` if the model does
     *  not exist.
     */
    public function selectOne(string $dirName, string $fileName, string $extension): ?array;

    /**
     * Returns all Halcyon models (templates) within a given directory.
     *
     * You can provide multiple options with the `$options` property, in order to filter the retrieved records:
     *  - `columns`: Only retrieve certain columns. Must be an array with any combination of `fileName`, `mtime` and
     *      `content`.
     *  - `extensions`: Defines the accepted extensions as an array. Eg: `['htm', 'md', 'twig']`
     *  - `fileMatch`: Defines a glob string to match filenames against. Eg: `'*gr[ae]y'`
     *  - `orders`: Not implemented
     *  - `limit`: Not implemented
     *  - `offset`: Not implemented
     *
     * @todo Implement support for `orders`, `limit` and `offset` options.
     * @param string $dirName The directory in which the model is stored.
     * @param array $options Defines the options for this query.
     * @return array An array of models found, with the columns defined as per the `columns` parameter for `$options`.
     */
    public function select(string $dirName, array $options = []): array;

    /**
     * Creates a new Halcyon model (template).
     *
     * @param string $dirName The directory in which the model is stored.
     * @param string $fileName The filename of the model.
     * @param string $extension The file extension of the model.
     * @param string $content The content to store for the model.
     * @return int The filesize of the created model.
     */
    public function insert(string $dirName, string $fileName, string $extension, string $content);

    /**
     * Updates an existing Halcyon model (template).
     *
     * @param string $dirName The directory in which the model is stored.
     * @param string $fileName The filename of the model.
     * @param string $extension The file extension of the model.
     * @param string $content The content to store for the model.
     * @param string|null $oldFileName Used for renaming templates. If specified, this will delete the "old" path.
     * @param string|null $oldExtension Used for renaming templates. If specified, this will delete the "old" path.
     * @return int The filesize of the updated model.
     */
    public function update(
        string $dirName,
        string $fileName,
        string $extension,
        string $content,
        ?string $oldFileName = null,
        ?string $oldExtension = null
    ): int;

    /**
     * Runs a delete statement against the datasource.
     *
     * @param string $dirName The directory in which the model is stored.
     * @param string $fileName The filename of the model.
     * @param string $extension The file extension of the model.
     * @return bool If the delete operation completed successfully.
     */
    public function delete(string $dirName, string $fileName, string $extension): bool;

    /**
     * Runs a delete statement against the datasource, forcing the complete removal of the model (template).
     *
     * @param string $dirName The directory in which the model is stored.
     * @param string $fileName The filename of the model.
     * @param string $extension The file extension of the model.
     * @return bool If the delete operation completed successfully.
     */
    public function forceDelete(string $dirName, string $fileName, string $extension): bool;

    /**
     * Returns the last modified date of a model (template).
     *
     * @param string $dirName The directory in which the model is stored.
     * @param string $fileName The filename of the model.
     * @param string $extension The file extension of the model.
     * @return int|null The last modified time as a timestamp, or `null` if the object doesn't exist.
     */
    public function lastModified(string $dirName, string $fileName, string $extension): ?int;

    /**
     * Generate a cache key unique to this datasource.
     *
     * @param string $name The name of the key.
     * @return string The hashed key.
     */
    public function makeCacheKey(string $name = ''): string;

    /**
     * Gets the prefix of the cache keys.
     *
     * This is based off a prefix including the base path for the model.
     *
     * @return string The cache key prefix.
     */
    public function getPathsCacheKey(): string;

    /**
     * Get all available paths within this datasource.
     *
     * This method returns an array, with all available paths as the key, and a boolean that represents whether the path
     * can be handled or modified.
     *
     * Example:
     *
     * ```php
     * [
     *     'path/to/file.md' => true, // (this path is available, and can be handled)
     *     'path/to/file2.md' => false // (this path is available, but cannot be handled)
     * ]
     * ```
     *
     * @return array An array of available paths alongside whether they can be handled.
     */
    public function getAvailablePaths(): array;
}
