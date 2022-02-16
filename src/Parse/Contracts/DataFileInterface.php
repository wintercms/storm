<?php namespace Winter\Storm\Parse\Contracts;

interface DataFileInterface
{
    /**
     * Return a new instance of `DataFileInterface` ready for modification of the provided filepath.
     */
    public static function open(string $filePath): ?DataFileInterface;

    /**
     * Set a property within the data.
     *
     * @param string|array $key
     * @param mixed|null $value
     */
    public function set($key, $value = null): DataFileInterface;

    /**
     * Write the current data to a file
     *
     * @param string|null $filePath
     */
    public function write(string $filePath = null): void;

    /**
     * Get the printed data
     */
    public function render(): string;
}
