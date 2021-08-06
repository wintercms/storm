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
     * @var array contains the env lookup map
     */
    protected $map = [];

    /**
     * @var string|null contains the filepath used to read / write
     */
    protected $file = null;

    /**
     * EnvFile constructor.
     * @param array $env
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;

        list($this->env, $this->map) = $this->parse($file);
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

        return new static($file);
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

        if (!isset($this->map[$key])) {
            $this->env[] = [
                'type' => 'var',
                'key' => $key,
                'value' => $value
            ];

            $this->map[$key] = count($this->env) - 1;

            return $this;
        }

        $this->env[$this->map[$key]]['value'] = $value;

        return $this;
    }

    /**
     * Push a newline onto the end of the env file
     *
     * @return $this
     */
    public function addNewLine(): EnvFile
    {
        $this->env[] = [
            'type' => 'nl'
        ];

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
        foreach ($this->env as $env) {
            if ($env['type'] === 'nl') {
                $out .= PHP_EOL;
                continue;
            }

            if ($env['type'] === 'comment') {
                $out .= $env['value'] . PHP_EOL;
                continue;
            }

            $out .= $env['key'] . '=' . $this->wrapValue($env['value']) . PHP_EOL;
        }

        return $out;
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
     * Parse a .env file, returns an array of the env file data and a key => pos map
     *
     * @param string $file
     * @return array
     */
    protected function parse(string $file): array
    {
        if (!file_exists($file) || !($contents = file($file)) || !count($contents)) {
            return [[], []];
        }

        $env = [];
        $map = [];
        $commentCounter = 0;

        foreach ($contents as $line) {
            switch (!($line = trim($line)) ? 'nl' : ((strpos($line, '#') === 0) ? 'comment' : 'var')) {
                case 'nl':
                    $env[] = [
                        'type' => 'nl'
                    ];
                    break;
                case 'comment':
                    $env[] = [
                        'type'  => 'comment',
                        'key'   => 'comment' . $commentCounter++,
                        'value' => $line
                    ];
                    break;
                case 'var':
                    $parts = explode('=', $line);
                    $env[] = [
                        'type'  => 'var',
                        'key'   => $parts[0],
                        'value' => trim($parts[1], '"')
                    ];
                    break;
            }
        }

        foreach ($env as $index => $item) {
            if ($item['type'] !== 'var') {
                continue;
            }
            $map[$item['key']] = $index;
        }

        return [$env, $map];
    }

    /**
     * Get the current env array
     *
     * @return array
     */
    public function getEnv(): array
    {
        $env = [];

        foreach ($this->env as $item) {
            if ($item['type'] !== 'var') {
                continue;
            }
            $env[$item['key']] = $item['value'];
        }

        return $env;
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
