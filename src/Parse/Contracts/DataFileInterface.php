<?php namespace Winter\Storm\Parse\Contracts;

interface DataFileInterface
{
    /**
     * Return a new instance of `DataFileInterface` ready for modification of the provided filepath.
     */
    public static function open(string $filePath): static;

    /**
     * Set a property within the data.
     */
    public function set(string|array $key, $value = null): static;

    /**
     * Write the current data to a file
     */
    public function write(?string $filePath = null): void;

    /**
     * Get the printed data
     */
    public function render(): string;
}
