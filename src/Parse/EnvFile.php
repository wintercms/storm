<?php namespace  Winter\Storm\Parse;

use Winter\Storm\Parse\Contracts\FileInterface;

/**
 * Class EnvFile
 */
class EnvFile implements FileInterface
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
    protected $filePath = null;

    /**
     * EnvFile constructor.
     * @param array $env
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

        list($this->env, $this->map) = $this->parse($filePath);
    }

    /**
     * Return a new instance of `EnvFile` ready for modification of the file.
     */
    public static function read(?string $filePath = null): ?EnvFile
    {
        if (!$filePath) {
            $filePath = base_path('.env');
        }

        return new static($filePath);
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
            $filePath = $this->filePath;
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
            switch ($env['type']) {
                case 'comment':
                    $out .= $env['value'];
                    break;
                case 'var':
                    $out .= $env['key'] . '=' . $this->escapeValue($env['value']);
                    break;
            }

            $out .= PHP_EOL;
        }

        return $out;
    }

    /**
     * Wrap a value in quotes if needed
     *
     * @param $value
     * @return string
     */
    protected function escapeValue($value): string
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
                // addslashes() wont work as it'll escape single quotes and they will be read literally
                return '"' . str_replace('"', '\"', $value) . '"';
        }
    }

    /**
     * Parse a .env file, returns an array of the env file data and a key => pos map
     *
     * @param string $filePath
     * @return array
     */
    protected function parse(string $filePath): array
    {
        if (!file_exists($filePath) || !($contents = file($filePath)) || !count($contents)) {
            return [[], []];
        }

        $env = [];
        $map = [];

        foreach ($contents as $line) {
            $type = !($line = trim($line))
                ? 'nl'
                : (
                    str_starts_with($line, '#')
                        ? 'comment'
                        : 'var'
                );

            $entry = [
                'type' => $type
            ];

            if ($type === 'var') {
                if (strpos($line, '=') === false) {
                    // if we cannot split the string, handle it the same as a comment
                    // i.e. inject it back into the file as is
                    $entry['type'] = $type = 'comment';
                } else {
                    list($key, $value) = explode('=', $line);
                    $entry['key'] = trim($key);
                    $entry['value'] = trim($value, '"');
                }
            }

            if ($type === 'comment') {
                $entry['value'] = $line;
            }

            $env[] = $entry;
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
}
