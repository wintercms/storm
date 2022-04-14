<?php namespace Winter\Storm\Parse\PHP;

use PhpParser\Error;
use PhpParser\Lexer;
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
    protected ?array $ast = null;

    /**
     * Lexer for use by `PhpParser`
     */
    protected ?Lexer $lexer = null;

    /**
     * Path to the file
     */
    protected ?string $filePath = null;

    /**
     * Printer used to define output syntax
     */
    protected PrettyPrinterAbstract|ArrayPrinter|null $printer = null;

    /**
     * Index of ast containing return stmt
     */
    protected ?int $astReturnIndex = null;

    /**
     * ArrayFile constructor.
     */
    final public function __construct(array $ast, Lexer $lexer, string $filePath = null, PrettyPrinterAbstract $printer = null)
    {
        $this->astReturnIndex = $this->getAstReturnIndex($ast);

        if (is_null($this->astReturnIndex)) {
            throw new \InvalidArgumentException('ArrayFiles must start with a return statement');
        }

        $this->ast = $ast;
        $this->lexer = $lexer;
        $this->filePath = $filePath;
        $this->printer = $printer ?? new ArrayPrinter();
    }

    /**
     * Return a new instance of `ArrayFile` ready for modification of the file.
     *
     * @throws \InvalidArgumentException if the provided path doesn't exist and $throwIfMissing is true
     * @throws SystemException if the provided path is unable to be parsed
     */
    public static function open(string $filePath, bool $throwIfMissing = false): static
    {
        $exists = file_exists($filePath);

        if (!$exists && $throwIfMissing) {
            throw new \InvalidArgumentException('file not found');
        }

        $lexer = new Lexer\Emulative([
            'usedAttributes' => [
                'comments',
                'startTokenPos',
                'startLine',
                'endTokenPos',
                'endLine'
            ]
        ]);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $lexer);

        try {
            $ast = $parser->parse(
                $exists
                    ? file_get_contents($filePath)
                    : sprintf('<?php%1$s%1$sreturn [];%1$s', "\n")
            );
        } catch (Error $e) {
            throw new SystemException($e);
        }

        return new static($ast, $lexer, $filePath);
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
     */
    public function set(string|array $key, $value = null): static
    {
        if (is_array($key)) {
            foreach ($key as $name => $value) {
                $this->set($name, $value);
            }

            return $this;
        }

        // try to find a reference to ast object
        list($target, $remaining) = $this->seek(explode('.', $key), $this->ast[$this->astReturnIndex]->expr);

        $valueType = $this->getType($value);

        // part of a path found
        if ($target && $remaining) {
            $target->value->items[] = $this->makeArrayItem(implode('.', $remaining), $valueType, $value);
            return $this;
        }

        // path to not found
        if (is_null($target)) {
            $this->ast[$this->astReturnIndex]->expr->items[] = $this->makeArrayItem($key, $valueType, $value);
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
     * @throws \RuntimeException If $type is not one of 'string', 'boolean', 'integer', 'function', 'const', 'null', or 'array'
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
                throw new \RuntimeException("An unimlemented replacement type ($type) was encountered");
        }
    }

    /**
     * Cast an array to AST
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
     * @param mixed $value
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
     * Find the return position within the ast, returns null on encountering an unsupported ast stmt.
     *
     * @param array $ast
     * @return int|null
     */
    protected function getAstReturnIndex(array $ast): ?int
    {
        foreach ($ast as $index => $item) {
            switch (get_class($item)) {
                case Stmt\Use_::class:
                case Stmt\Expression::class:
                    break;
                case Stmt\Return_::class:
                    return $index;
                default:
                    return null;
            }
        }

        return null;
    }

    /**
     * Attempt to find the parent object of the targeted path.
     * If the path cannot be found completely, return the nearest parent and the remainder of the path
     *
     * @param array $path
     * @param $pointer
     * @param int $depth
     * @throws SystemException if trying to set a position that is already occupied by a value
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
     * @throws \InvalidArgumentException if the provided sort type is not a callable or one of static::SORT_ASC or static::SORT_DESC
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
                throw new \InvalidArgumentException('Requested sort type is invalid');
        }

        return $this;
    }

    /**
     * Recursive sort an Array_ item array
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
     */
    public function function(string $name, array $args): PHPFunction
    {
        return new PHPFunction($name, $args);
    }

    /**
     * Returns a new instance of PHPConstant
     */
    public function constant(string $name): PHPConstant
    {
        return new PHPConstant($name);
    }

    /**
     * Get the printed AST as PHP code
     */
    public function render(): string
    {
        return $this->printer->render($this->ast, $this->lexer) . "\n";
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
