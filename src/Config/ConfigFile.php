<?php namespace Winter\Storm\Config;

use Winter\Storm\Config\ConfigFileInterface;
use PhpParser\Error;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinterAbstract;
use Winter\Storm\Exception\ApplicationException;

/**
 * Class ConfigFile
 * @package Winter\Storm\Config
 */
class ConfigFile implements ConfigFileInterface
{
    /**
     * @var Stmt[]|null Abstract syntax tree produced by `PhpParser`
     */
    protected $ast = null;
    /**
     * @var string|null Source config file
     */
    protected $file = null;
    /**
     * @var PrettyPrinterAbstract|WinterPrinter|null Printer used to define output syntax
     */
    protected $printer = null;

    /**
     * ConfigFile constructor.
     *
     * @param Stmt[]|null $ast
     * @param string $file
     * @param PrettyPrinterAbstract|null $printer
     */
    public function __construct(array $ast, string $file = null, PrettyPrinterAbstract $printer = null)
    {
        if (!($ast[0] instanceof Stmt\Return_)) {
            throw new \InvalidArgumentException('configs must start with a return statement');
        }

        $this->ast = $ast;
        $this->file = $file;
        $this->printer = $printer ?? new WinterPrinter();
    }

    /**
     * Return a new instance of `ConfigFile` ready for modification of the file.
     *
     * @param string $file
     * @param bool $createMissing
     * @return ConfigFile|null
     */
    public static function read(string $file, bool $createMissing = false): ?ConfigFile
    {
        $exists = file_exists($file);

        if (!$exists && !$createMissing) {
            throw new \InvalidArgumentException('file not found');
        }

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        try {
            $ast = $parser->parse(
                $exists
                    ? file_get_contents($file)
                    : sprintf('<?php%1$s%1$sreturn [];%1$s', PHP_EOL)
            );
        } catch (Error $e) {
            throw new \ApplicationException($e);
        }

        return new static($ast, $file);
    }

    /**
     * Set a property within the config using dot notation. Passing an array as param 1 is also supported.
     *
     * ```php
     * $config->set('property.key.value', 'example');
     * // or
     * $config->set([
     *     'property.key1.value' => 'example',
     *     'property.key2.value' => 'example'
     * ]);
     * ```
     *
     * @param string|array $key
     * @param mixed|null $value
     * @return $this
     */
    public function set($key, $value = null): ConfigFile
    {
        if (is_array($key)) {
            foreach ($key as $name => $value) {
                $this->set($name, $value);
            }

            return $this;
        }

        if ($key && is_null($value)) {
            throw new ApplicationException('You must specify a value to set for the given key.');
        }

        // try to find a reference to ast object
        list($target, $remaining) = $this->seek(explode('.', $key), $this->ast[0]->expr);

        $valueType = gettype($value);

        // part of a path found
        if ($target && $remaining) {
            $target->value->items[] = $this->makeArrayItem(implode('.', $remaining), $valueType, $value);
            return $this;
        }

        // path to not found
        if (is_null($target)) {
            $this->ast[0]->expr->items[] = $this->makeArrayItem($key, $valueType, $value);
            return $this;
        }

        if (!isset($target->value)) {
            return $this;
        }

        // special handling of function objects
        if (get_class($target->value) === FuncCall::class && !$value instanceof ConfigFunction) {
            if ($target->value->name->parts[0] !== 'env' || !isset($target->value->args[0])) {
                return $this;
            }
            if (isset($target->value->args[0]) && !isset($target->value->args[1])) {
                $target->value->args[1] = new Arg(new String_(''));
            }
            $target->value->args[1]->value->value = $value;
            return $this;
        }

        // default update in place
        $target->value = $this->makeAstNode($valueType, $value);

        return $this;
    }

    /**
     * Creates either a simple array item or a recursive array of items
     *
     * @param string $key
     * @param string $valueType
     * @param $value
     * @return ArrayItem
     */
    protected function makeArrayItem(string $key, string $valueType, $value): ArrayItem
    {
        return (str_contains($key, '.'))
            ? $this->makeAstArrayRecursive($key, $valueType, $value)
            : new ArrayItem(
                $this->makeAstNode($valueType, $value),
                $this->makeAstNode(gettype($key), $key)
            );
    }

    /**
     * Generate an AST node, using `PhpParser` classes, for a value
     *
     * @param string $type
     * @param mixed $value
     * @return ConstFetch|LNumber|String_|FuncCall
     */
    protected function makeAstNode(string $type, $value)
    {
        if ($value instanceof ConfigFunction) {
            $type = 'function';
        }

        switch ($type) {
            case 'string':
                return new String_($value);
            case 'boolean':
                return new ConstFetch(new Name($value ? 'true' : 'false'));
            case 'integer':
                return new LNumber($value);
            case 'function':
                return new FuncCall(
                    new Name($value->getName()),
                    array_map(function ($arg) {
                        return new Arg($this->makeAstNode(gettype($arg), $arg));
                    }, $value->getArgs())
                );
            default:
                throw new \RuntimeException('not implemented replacement type: ' . $type);
        }
    }

    /**
     * Returns an ArrayItem generated from a dot notation path
     *
     * @param string $key
     * @param string $valueType
     * @param $value
     * @return ArrayItem
     */
    protected function makeAstArrayRecursive(string $key, string $valueType, $value): ArrayItem
    {
        $path = array_reverse(explode('.', $key));

        $arrayItem = $this->makeAstNode($valueType, $value);

        foreach ($path as $index => $pathKey) {
            if (is_numeric($pathKey)) {
                $pathKey = (int) $pathKey;
            }
            $arrayItem = new ArrayItem($arrayItem, $this->makeAstNode(gettype($pathKey), $pathKey));

            if ($index !== array_key_last($path)) {
                $arrayItem = new Array_([$arrayItem]);
            }
        }

        return $arrayItem;
    }

    /**
     * Attempt to find the parent object of the targeted path.
     * If the path cannot be found completely, return the nearest parent and the remainder of the path
     *
     * @param array $path
     * @param $pointer
     * @param int $depth
     * @return array
     */
    protected function seek(array $path, &$pointer, int $depth = 0): array
    {
        if (!$pointer) {
            return [null, $path];
        }

        $key = array_shift($path);

        if (isset($pointer->value) && !($pointer->value instanceof ArrayItem || $pointer->value instanceof Array_)) {
            throw new ApplicationException(sprintf(
                'Illegal offset, you are trying to set a position occupied by a value (%s)',
                get_class($pointer->value)
            ));
        }

        foreach (($pointer->items ?? $pointer->value->items) as $index => &$item) {
            // loose checking to allow for int keys
            if ($item->key->value == $key) {
                if (!empty($path)) {
                    return $this->seek($path, $item, ++$depth);
                }

                return [$item, []];
            }
        }

        array_unshift($path, $key);

        return [($depth > 0) ? $pointer : null, $path];
    }

    /**
     * Write the current config to a file
     *
     * @param string|null $filePath
     * @return void
     */
    public function write(string $filePath = null): void
    {
        if (!$filePath && $this->file) {
            $filePath = $this->file;
        }

        file_put_contents($filePath, $this->render());
    }

    public function function(string $name, array $args): ConfigFunction
    {
        return new ConfigFunction($name, $args);
    }

    /**
     * Get the printed AST as php code
     *
     * @return string
     */
    public function render(): string
    {
        return $this->printer->prettyPrintFile($this->ast) . PHP_EOL;
    }

    /**
     * Get currently loaded AST
     *
     * @return Stmt[]|null
     */
    public function getAst()
    {
        return $this->ast;
    }
}
