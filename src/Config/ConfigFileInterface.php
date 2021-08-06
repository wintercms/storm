<?php namespace Winter\Storm\Config;

// Returns are commented out as improved type variance is not supported until php 7.4
// Ref: https://stitcher.io/blog/new-in-php-74#improved-type-variance-rfc
// TODO: enable return types once support for <7.4 is dropped

/**
 * Interface ConfigFileInterface
 * @package Winter\Storm\Config
 */
interface ConfigFileInterface
{
    /**
     * Return a new instance of `ConfigFileInterface` ready for modification of the file.
     *
     * @param string $file
     * @return ConfigFile|null
     */
    public static function read(string $file); //: ?ConfigFileInterface;

    /**
     * Set a property within the config. Passing an array as param 1 is also supported.
     * @param string|array $key
     * @param mixed|null $value
     * @return $this
     */
    public function set($key, $value = null); //: ConfigFileInterface;

    /**
     * Write the current config to a file
     *
     * @param string|null $filePath
     * @return void
     */
    public function write(string $filePath = null): void;

    /**
     * Get the printed config
     *
     * @return string
     */
    public function render(): string;
}
