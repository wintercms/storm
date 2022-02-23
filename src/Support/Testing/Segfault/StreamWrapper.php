<?php namespace Winter\Storm\Support\Testing\Segfault;

/**
 * Segfault StreamWrapper class
 *
 * Handles registering the StreamWrapper that will
 * applythe StreamFilter toany pure PHP files opened
 *
 * @see https://gist.github.com/lyrixx/56dfc48fb7e807dd2a229813da89a0dc#hardcore-debug-logger
 */
class StreamWrapper
{
    const PROTOCOLS = ['file', 'phar'];
    const STREAM_OPEN_FOR_INCLUDE = 128;

    protected $files;
    public $context;
    public $resource;

    public static function register()
    {
        foreach (static::PROTOCOLS as $protocol) {
            stream_wrapper_unregister($protocol);
            stream_wrapper_register($protocol, static::class);
        }
    }

    public static function unregister()
    {
        foreach (static::PROTOCOLS as $protocol) {
            set_error_handler(function () {});
            stream_wrapper_restore($protocol);
            restore_error_handler();
        }
    }

    public function dir_closedir(): bool
    {
        closedir($this->resource);

        return true;
    }

    public function dir_opendir(string $path, int $options): bool
    {
        $this->resource = $this->wrapCallWithContext('opendir', $path);

        return false !== $this->resource;
    }

    public function dir_readdir()
    {
        return readdir($this->resource);
    }

    public function dir_rewinddir(): bool
    {
        rewinddir($this->resource);

        return true;
    }

    public function mkdir(string $path, int $mode, int $options): bool
    {
        $recursive = (bool) ($options & STREAM_MKDIR_RECURSIVE);

        return $this->wrapCallWithContext('mkdir', $path, $mode, $recursive);
    }

    public function rename(string $path_from, string $path_to): bool
    {
        return $this->wrapCallWithContext('rename', $path_from, $path_to);
    }

    public function rmdir(string $path, int $options): bool
    {
        return $this->wrapCallWithContext('rmdir', $path);
    }

    public function stream_cast(int $cast_as)
    {
        return $this->resource;
    }

    public function stream_close()
    {
        fclose($this->resource);
    }

    public function stream_eof(): bool
    {
        return feof($this->resource);
    }

    public function stream_flush(): bool
    {
        return fflush($this->resource);
    }

    public function stream_lock(int $operation): bool
    {
        return flock($this->resource, $operation);
    }

    public function stream_metadata(string $path, int $option, $value): bool
    {
        return $this->wrapCall(function (string $path, int $option, $value) {
            switch ($option) {
                case STREAM_META_TOUCH:
                    if (empty($value)) {
                        $result = touch($path);
                    } else {
                        $result = touch($path, $value[0], $value[1]);
                    }
                    break;
                case STREAM_META_OWNER_NAME:
                case STREAM_META_OWNER:
                    $result = chown($path, $value);
                    break;
                case STREAM_META_GROUP_NAME:
                case STREAM_META_GROUP:
                    $result = chgrp($path, $value);
                    break;
                case STREAM_META_ACCESS:
                    $result = chmod($path, $value);
                    break;
            }
        }, $path, $option, $value);
    }

    public function stream_open(string $path, string $mode, int $options, string &$opened_path = null): bool
    {
        $useIncludePath = (bool) ($options & STREAM_USE_PATH);

        $this->resource = $this->wrapCallWithContext('fopen', $path, $mode, $useIncludePath);

        $including = (bool) ($options & static::STREAM_OPEN_FOR_INCLUDE);
        if ($including && false !== $this->resource) {
            StreamFilter::append($this->resource, $path);
        }

        return false !== $this->resource;
    }

    public function stream_read(int $count): string
    {
        return fread($this->resource, $count);
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return fseek($this->resource, $offset, $whence);
    }

    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                return stream_set_blocking($this->resource, $arg1);
            case STREAM_OPTION_READ_TIMEOUT:
                return stream_set_timeout($this->resource, $arg1, $arg2);
            case STREAM_OPTION_WRITE_BUFFER:
                return stream_set_write_buffer($this->resource, $arg1);
            case STREAM_OPTION_READ_BUFFER:
                return stream_set_read_buffer($this->resource, $arg1);
        }
    }

    public function stream_stat(): array
    {
        return fstat($this->resource);
    }

    public function stream_tell(): int
    {
        return ftell($this->resource);
    }

    public function stream_truncate(int $new_size): bool
    {
        return ftruncate($this->resource, $new_size);
    }

    public function stream_write(string $data): int
    {
        return fwrite($this->resource, $data);
    }

    public function unlink(string $path): bool
    {
        return $this->wrapCallWithContext('unlink', $path);
    }

    public function url_stat(string $path, int $flags)
    {
        $result = @$this->wrapCall('stat', $path);
        if (false === $result) {
            $result = null;
        }

        return $result;
    }

    protected function wrapCallWithContext(callable $function, ...$args)
    {
        if ($this->context) {
            $args[] = $this->context;
        }

        return $this->wrapCall($function, ...$args);
    }

    protected function wrapCall(callable $function, ...$args)
    {
        try {
            foreach (static::PROTOCOLS as $protocol) {
                set_error_handler(function () {
                });
                stream_wrapper_restore($protocol);
                restore_error_handler();
            }

            return $function(...$args);
        } catch (\Throwable $e) {
            return false;
        } finally {
            foreach (static::PROTOCOLS as $protocol) {
                stream_wrapper_unregister($protocol);
                stream_wrapper_register($protocol, static::class);
            }
        }
    }
}