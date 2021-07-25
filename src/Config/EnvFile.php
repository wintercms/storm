<?php namespace  Winter\Storm\Config;

use Winter\Storm\Config\ConfigFileInterface;
use Dotenv\Environment\DotenvFactory;
use Dotenv\Loader;

/**
 * Class EnvFile
 * @package Winter\Storm\Config
 */
class EnvFile implements ConfigFileInterface
{
    /**
     * @var array contains the env during modification
     */
    protected $env = [];

    /**
     * @var string|null contains the filepath used to read / write
     */
    protected $file = null;

    /**
     * EnvFile constructor.
     * @param array $env
     * @param string $file
     */
    public function __construct(array $env, string $file)
    {
        $this->env = $env;
        $this->file = $file;
    }

    /**
     * Return a new instance of `EnvFile` ready for modification of the file.
     *
     * @param string|null $file
     * @return EnvFile|null
     */
    public static function read(?string $file = null): ?EnvFile
    {
        if (!$file) {
            $file = static::getEnvFilePath();
        }

        $loader = new Loader([$file], new DotenvFactory(), false);

        return new static($loader->load(), $file);
    }

    /**
     * Set a property within the env. Passing an array as param 1 is also supported.
     *
     * ```php
     * $env->set('APP_PROPERTY', 'example');
     * // or
     * $env->set([
     *     'APP_PROPERTY' => 'example',
     *     'DIF_PROPERTY' => 'example'
     * ]);
     * ```
     * @param array|string $key
     * @param mixed|null $value
     * @return $this
     */
    public function set($key, $value = null): EnvFile
    {
        if (is_array($key)) {
            foreach ($key as $item => $value) {
                $this->set($item, $value);
            }
            return $this;
        }

        $this->env[$key] = $value;

        return $this;
    }

    /**
     * Write the current env to a file
     *
     * @param string|null $filePath
     */
    public function write(string $filePath = null): void
    {
        if (!$filePath) {
            $filePath = $this->file;
        }

        file_put_contents($filePath, $this->render());
    }

    /**
     * Get the env as a string
     *
     * @return string
     */
    public function render(): string
    {
        $out = '';
        $key = null;
        // count the elements in each block
        $count = 0;

        $arrayKeys = array_keys($this->env);

        foreach ($this->env as $item => $value) {
            // get the prefix eg. DB_
            $prefix = explode('_', $item)[0] ?? null;

            if ($key && $key !== $prefix) {
                // get the position of the prefix in the next position of $this->env
                $pos = $this->strpos($arrayKeys[array_search($item, $arrayKeys) + 1] ?? '', $prefix);
                if ($pos === 0 || $count > 1) {
                    $out .= PHP_EOL;
                    $count = 0;
                }
            }

            if ($key && $key === $prefix) {
                $count++;
            }

            $key = $prefix;

            $out .= $item . '=' . $this->wrapValue($value) . PHP_EOL;
        }

        return $out;
    }

    /**
     * Allow for haystack check before execution
     *
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @return false|int
     */
    public function strpos(string $haystack, string $needle, int $offset = 0)
    {
        if (!$haystack) {
            return false;
        }

        return \strpos($haystack, $needle, $offset);
    }

    /**
     * Wrap a value in quotes if needed
     *
     * @param $value
     * @return string
     */
    protected function wrapValue($value): string
    {
        if (is_numeric($value)) {
            return $value;
        }

        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        if ($value === null) {
            return 'null';
        }

        switch ($value) {
            case 'true':
            case 'false':
            case 'null':
                return $value;
            default:
                return '"' . $value . '"';
        }
    }

    /**
     * Get the current env array
     *
     * @return array
     */
    public function getEnv(): array
    {
        return $this->env;
    }

    /**
     * Get the default env file path
     *
     * @return string
     */
    public static function getEnvFilePath(): string
    {
        return base_path('.env');
    }
}
