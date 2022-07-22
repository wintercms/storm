<?php namespace Winter\Storm\Halcyon\Datasource;

use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Filesystem\PathResolver;
use Winter\Storm\Halcyon\Processors\Processor;
use Winter\Storm\Halcyon\Exception\CreateFileException;
use Winter\Storm\Halcyon\Exception\DeleteFileException;
use Winter\Storm\Halcyon\Exception\FileExistsException;
use Winter\Storm\Halcyon\Exception\InvalidFileNameException;
use Winter\Storm\Halcyon\Exception\CreateDirectoryException;

/**
 * File based datasource.
 */
class FileDatasource extends Datasource
{
    /**
     * The local path where the datasource can be found.
     */
    protected string $basePath;

    /**
     * The filesystem instance.
     */
    protected \Winter\Storm\Filesystem\Filesystem $files;

    /**
     * Resolved path map.
     *
     * @var array
     */
    protected $resolvedBasePaths = [];

    /**
     * Create a new datasource instance.
     *
     * @param string $basePath
     * @param Filesystem $files
     * @return void
     */
    public function __construct(string $basePath, Filesystem $files)
    {
        $this->basePath = $basePath;
        $this->files = $files;
        $this->postProcessor = new Processor;
    }

    /**
     * @inheritDoc
     */
    public function selectOne(string $dirName, string $fileName, string $extension): ?array
    {
        try {
            $path = $this->makeFilePath($dirName, $fileName, $extension);

            return [
                'fileName' => $fileName . '.' . $extension,
                'content'  => $this->files->get($path),
                'mtime'    => $this->files->lastModified($path)
            ];
        } catch (Exception $ex) {
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function select(string $dirName, array $options = []): array
    {
        // Prepare query options
        $queryOptions = array_merge([
            'columns'     => null,  // Only return specific columns (fileName, mtime, content)
            'extensions'  => null,  // Match specified extensions
            'fileMatch'   => null,  // Match the file name using fnmatch()
            'orders'      => null,  // @todo
            'limit'       => null,  // @todo
            'offset'      => null   // @todo
        ], $options);
        extract($queryOptions);

        $result = [];
        $dirPath = $this->makeDirectoryPath($dirName);

        if (!$this->files->isDirectory($dirPath)) {
            return $result;
        }

        if (isset($columns)) {
            if ($columns === ['*'] || !is_array($columns)) {
                $columns = null;
            } else {
                $columns = array_flip($columns);
            }
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath));
        $it->setMaxDepth(1); // Support only a single level of subdirectories
        $it->rewind();

        while ($it->valid()) {
            if (!$it->isFile()) {
                $it->next();
                continue;
            }

            /*
             * Filter by extension
             */
            $fileExt = $it->getExtension();
            if (isset($extensions) && !in_array($fileExt, $extensions)) {
                $it->next();
                continue;
            }

            $fileName = $it->getBasename();
            if ($it->getDepth() > 0) {
                $fileName = basename($it->getPath()).'/'.$fileName;
            }

            /*
             * Filter by file name match
             */
            if (isset($fileMatch) && !fnmatch($fileMatch, $fileName)) {
                $it->next();
                continue;
            }

            $item = [];

            $path = $this->makeDirectoryPath($dirName, $fileName);

            $item['fileName'] = $fileName;

            if (!isset($columns) || array_key_exists('content', $columns)) {
                $item['content'] = $this->files->get($path);
            }

            if (!isset($columns) || array_key_exists('mtime', $columns)) {
                $item['mtime'] = $this->files->lastModified($path);
            }

            $result[] = $item;

            $it->next();
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function insert(string $dirName, string $fileName, string $extension, string $content): int
    {
        $this->validateDirectoryForSave($dirName, $fileName, $extension);

        $path = $this->makeFilePath($dirName, $fileName, $extension);

        if ($this->files->isFile($path)) {
            throw (new FileExistsException)->setInvalidPath($path);
        }

        try {
            return $this->files->put($path, $content);
        } catch (Exception $ex) {
            throw (new CreateFileException)->setInvalidPath($path);
        }
    }

    /**
     * @inheritDoc
     */
    public function update(string $dirName, string $fileName, string $extension, string $content, ?string $oldFileName = null, ?string $oldExtension = null): int
    {
        $this->validateDirectoryForSave($dirName, $fileName, $extension);

        $path = $this->makeFilePath($dirName, $fileName, $extension);

        /*
         * The same file is safe to rename when the case is changed
         * eg: FooBar -> foobar
         */
        $iFileChanged = ($oldFileName !== null && strcasecmp($oldFileName, $fileName) !== 0) ||
            ($oldExtension !== null && strcasecmp($oldExtension, $extension) !== 0);

        if ($iFileChanged && $this->files->isFile($path)) {
            throw (new FileExistsException)->setInvalidPath($path);
        }

        /*
         * File to be renamed, as delete and recreate
         */
        $fileChanged = ($oldFileName !== null && strcmp($oldFileName, $fileName) !== 0) ||
            ($oldExtension !== null && strcmp($oldExtension, $extension) !== 0);

        if ($fileChanged) {
            $this->delete($dirName, $oldFileName, $oldExtension);
        }

        try {
            return $this->files->put($path, $content);
        } catch (Exception $ex) {
            throw (new CreateFileException)->setInvalidPath($path);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $dirName, string $fileName, string $extension): bool
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        try {
            return $this->files->delete($path);
        } catch (Exception $ex) {
            throw (new DeleteFileException)->setInvalidPath($path);
        }
    }

    /**
     * @inheritDoc
     */
    public function lastModified(string $dirName, string $fileName, string $extension): ?int
    {
        try {
            $path = $this->makeFilePath($dirName, $fileName, $extension);

            return $this->files->lastModified($path);
        } catch (Exception $ex) {
            return null;
        }
    }

    /**
     * Ensure the requested file can be created in the requested directory.
     *
     * @param string $dirName The directory in which the model is stored.
     * @param string $fileName The filename of the model.
     * @param string $extension The file extension of the model.
     */
    protected function validateDirectoryForSave(string $dirName, string $fileName, string $extension): void
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);
        $dirPath = $this->makeDirectoryPath($dirName);

        /*
         * Create base directory
         */
        if (
            (!$this->files->exists($dirPath) || !$this->files->isDirectory($dirPath)) &&
            !$this->files->makeDirectory($dirPath, 0777, true, true)
        ) {
            throw (new CreateDirectoryException)->setInvalidPath($dirPath);
        }

        /*
         * Create base file directory
         */
        if (($pos = strpos($fileName, '/')) !== false) {
            $fileDirPath = dirname($path);

            if (
                !$this->files->isDirectory($fileDirPath) &&
                !$this->files->makeDirectory($fileDirPath, 0777, true, true)
            ) {
                throw (new CreateDirectoryException)->setInvalidPath($fileDirPath);
            }
        }
    }

    /**
     * Helper to generate the absolute path to the provided relative path within the provided directory
     *
     * @param string $dirName
     * @param string $relativePath Optional, if not provided the absolute path to the provided directory will be returned
     * @throws InvalidFileNameException If the path is outside of the basePath of the datasource
     * @return string
     */
    protected function makeDirectoryPath(string $dirName, string $relativePath = ''): string
    {
        $base = $this->basePath . '/' . $dirName;
        $path = !empty($relativePath) ? $base . '/' . $relativePath : $base;

        // Resolve paths with base lookup for performance
        $base = $this->resolvedBasePaths[$base] ?? ($this->resolvedBasePaths[$base] = PathResolver::resolve($base));
        $path = PathResolver::resolve($path);

        // Limit paths to those under the configured basePath + directory combo
        if (!starts_with($path, $base)) {
            throw (new InvalidFileNameException)->setInvalidFileName($path);
        }

        return $path;
    }

    /**
     * Helper method to make the full file path to the model.
     *
     * @param string $dirName The directory in which the model is stored.
     * @param string $fileName The filename of the model.
     * @param string $extension The file extension of the model.
     * @return string The full file path.
     */
    protected function makeFilePath(string $dirName, string $fileName, string $extension): string
    {
        return $this->makeDirectoryPath($dirName, $fileName . '.' . $extension);
    }

    /**
     * Returns the base path for this datasource.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @inheritDoc
     */
    public function getPathsCacheKey(): string
    {
        return 'halcyon-datastore-file-' . $this->basePath;
    }

    /**
     * @inheritDoc
     */
    public function getAvailablePaths(): array
    {
        $pathsCache = [];
        $it = (is_dir($this->basePath))
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->basePath))
            : [];

        foreach ($it as $file) {
            if ($file->isDir()) {
                continue;
            }

            // Add the relative path, normalized
            $pathsCache[] = substr(
                $this->files->normalizePath($file->getPathname()),
                strlen($this->basePath) + 1
            );
        }

        // Format array in the form of ['path/to/file' => true];
        $pathsCache = array_map(function () {
            return true;
        }, array_flip($pathsCache));

        return $pathsCache;
    }
}
