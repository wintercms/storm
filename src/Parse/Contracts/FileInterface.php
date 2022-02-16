<?php namespace Winter\Storm\Parse\Contracts;

interface FileInterface
{
    /**
     * Return a new instance of `FileInterface` ready for modification of the file.
     *
     * @param string $file
     */
    public static function read(string $file): ?FileInterface;

    /**
     * Set a property within the config. Passing an array as param 1 is also supported.
     *
     * @param string|array $key
     * @param mixed|null $value
     */
    public function set($key, $value = null): FileInterface;

    /**
     * Write the current config to a file
     *
     * @param string|null $filePath
     */
    public function write(string $filePath = null): void;

    /**
     * Get the printed config
     */
    public function render(): string;
}
