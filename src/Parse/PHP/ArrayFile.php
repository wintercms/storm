<?php namespace Winter\Storm\Parse\PHP;

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
use Winter\Storm\Exception\SystemException;
use Winter\Storm\Parse\Contracts\DataFileInterface;

class ArrayFile implements DataFileInterface
{
    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';

    /**
     * @var Stmt[]|null Abstract syntax tree produced by `PhpParser`
     */
    protected $ast = null;

    /**
     * @var string|null Path to the file
     */
    protected $filePath = null;

    /**
     * @var PrettyPrinterAbstract|ArrayPrinter|null Printer used to define output syntax
     */
    protected $printer = null;

    /**
     * ArrayFile constructor.
     */
    public function __construct(array $ast, string $filePath = null, PrettyPrinterAbstract $printer = null)
    {
        if (!($ast[0] instanceof Stmt\Return_)) {
            throw new \InvalidArgumentException('ArrayFiles must start with a return statement');
        }

        $this->ast = $ast;
        $this->filePath = $filePath;
        $this->printer = $printer ?? new ArrayPrinter();
    }

    /**
     * Return a new instance of `ArrayFile` ready for modification of the file.
     *
     * @param string $filePath
     * @param bool $createMissing
     * @return ArrayFile|null
     */
    public static function open(string $filePath, bool $createMissing = false): ?ArrayFile
    {
        $exists = file_exists($filePath);

        if (!$exists && !$createMissing) {
            throw new \InvalidArgumentException('file not found');
        }

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        try {
            $ast = $parser->parse(
                $exists
                    ? file_get_contents($filePath)
                    : sprintf('<?php%1$s%1$sreturn [];%1$s', "\n")
            );
        } catch (Error $e) {
            throw new SystemException($e);
        }

        return new static($ast, $filePath);
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
    public function set($key, $value = null): ArrayFile
    {
        if (is_array($key)) {
            foreach ($key as $name => $value) {
                $this->set($name, $value);
            }

            return $this;
        }

        // try to find a reference to ast object
        list($target, $remaining) = $this->seek(explode('.', $key), $this->ast[0]->expr);

        $valueType = $this->getType($value);

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
        if (get_class($target->value) === FuncCall::class && $valueType !== 'function') {
            if ($target->value->name->parts[0] !== 'env' || !isset($target->value->args[0])) {
                return $this;
            }
            if (isset($target->value->args[0]) && !isset($target->value->args[1])) {
                $target->value->args[1] = new Arg($this->makeAstNode($valueType, $value));
            }
            $target->value->args[1]->value = $this->makeAstNode($valueType, $value);
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
                $this->makeAstNode($this->getType($key), $key)
            );
    }

    /**
     * Generate an AST node, using `PhpParser` classes, for a value
     *
     * @param string $type
     * @param mixed $value
     * @return ConstFetch|LNumber|String_|Array_|FuncCall
     */
    protected function makeAstNode(string $type, $value)
    {
        switch (strtolower($type)) {
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
                        return new Arg($this->makeAstNode($this->getType($arg), $arg));
                    }, $value->getArgs())
                );
            case 'const':
                return new ConstFetch(new Name($value->getName()));
            case 'null':
                return new ConstFetch(new Name('null'));
            case 'array':
                return $this->castArray($value);
            default:
                throw new \RuntimeException('not implemented replacement type: ' . $type);
        }
    }

    /**
     * Cast an array to AST
     *
     * @param array $array
     * @return Array_
     */
    protected function castArray(array $array): Array_
    {
        return ($caster = function ($array, $ast) use (&$caster) {
            $useKeys = [];
            foreach (array_keys($array) as $i => $key) {
                $useKeys[$key] = (!is_numeric($key) || $key !== $i);
            }
            foreach ($array as $key => $item) {
                if (is_array($item)) {
                    $ast->items[] = new ArrayItem(
                        $caster($item, new Array_()),
                        ($useKeys[$key] ? $this->makeAstNode($this->getType($key), $key) : null)
                    );
                    continue;
                }
                $ast->items[] = new ArrayItem(
                    $this->makeAstNode($this->getType($item), $item),
                    ($useKeys[$key] ? $this->makeAstNode($this->getType($key), $key) : null)
                );
            }

            return $ast;
        })($array, new Array_());
    }

    /**
     * Returns type of var passed
     *
     * @param mixed $var
     * @return string
     */
    protected function getType($var): string
    {
        if ($var instanceof PHPFunction) {
            return 'function';
        }

        if ($var instanceof PHPConstant) {
            return 'const';
        }

        return gettype($var);
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
            $arrayItem = new ArrayItem($arrayItem, $this->makeAstNode($this->getType($pathKey), $pathKey));

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
     * @throws SystemException
     */
    protected function seek(array $path, &$pointer, int $depth = 0): array
    {
        if (!$pointer) {
            return [null, $path];
        }

        $key = array_shift($path);

        if (isset($pointer->value) && !($pointer->value instanceof ArrayItem || $pointer->value instanceof Array_)) {
            throw new SystemException(sprintf(
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
     * Sort the config, supports: ArrayFile::SORT_ASC, ArrayFile::SORT_DESC, callable
     *
     * @param string|callable $mode
     */
    public function sort($mode = self::SORT_ASC): ArrayFile
    {
        if (is_callable($mode)) {
            usort($this->ast[0]->expr->items, $mode);
            return $this;
        }

        switch ($mode) {
            case static::SORT_ASC:
            case static::SORT_DESC:
                $this->sortRecursive($this->ast[0]->expr->items, $mode);
                break;
            default:
                throw new \InvalidArgumentException('sort type not implemented');
        }

        return $this;
    }

    /**
     * Recursive sort an Array_ item array
     *
     * @param array $array
     * @param string $mode
     * @return void
     */
    protected function sortRecursive(array &$array, string $mode): void
    {
        foreach ($array as &$item) {
            if (isset($item->value) && $item->value instanceof Array_) {
                $this->sortRecursive($item->value->items, $mode);
            }
        }

        usort($array, function ($a, $b) use ($mode) {
            return $mode === static::SORT_ASC
                ? $a->key->value <=> $b->key->value
                : $b->key->value <=> $a->key->value;
        });
    }

    /**
     * Write the current config to a file
     *
     * @param string|null $filePath
     * @return void
     */
    public function write(string $filePath = null): void
    {
        if (!$filePath && $this->filePath) {
            $filePath = $this->filePath;
        }

        file_put_contents($filePath, $this->render());
    }

    /**
     * Returns a new instance of PHPFunction
     *
     * @param string $name
     * @param array $args
     * @return PHPFunction
     */
    public function function(string $name, array $args): PHPFunction
    {
        return new PHPFunction($name, $args);
    }

    /**
     * Returns a new instance of PHPConstant
     *
     * @param string $name
     * @return PHPConstant
     */
    public function const(string $name): PHPConstant
    {
        return new PHPConstant($name);
    }

    /**
     * Get the printed AST as php code
     *
     * @return string
     */
    public function render(): string
    {
        return $this->printer->prettyPrintFile($this->ast) . "\n";
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
