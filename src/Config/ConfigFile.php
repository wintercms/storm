<?php namespace Winter\Storm\Config;

use PhpParser\Node\Expr\ArrayItem;
use Winter\Storm\Config\ConfigFileInterface;
use PhpParser\Error;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinterAbstract;

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
        if (!file_exists($file)) {
            if (!$createMissing) {
                throw new \InvalidArgumentException('file not found');
            }

            // create the file with an empty array
            file_put_contents($file, sprintf('<?php%1$s%1$sreturn [];%1$s', PHP_EOL));
        }

        $content = file_get_contents($file);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        try {
            $ast = $parser->parse($content);
        } catch (Error $e) {
            throw new ApplicationException($e);
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

        $valueType = gettype($value);
        
        if (!count($this->ast[0]->expr->items)) {
            $this->ast[0]->expr->items[] = new ArrayItem(
                $this->makeAstNode($valueType, $value),
                $this->makeAstNode(gettype($key), $key)
            );
            return $this;
        }

        $target = $this->seek(explode('.', $key), $this->ast[0]->expr->items);
        $class = get_class($target->value);

        if ($class === FuncCall::class) {
            if ($target->value->name->parts[0] !== 'env' || !isset($target->value->args[0])) {
                return $this;
            }
            if (isset($target->value->args[0]) && !isset($target->value->args[1])) {
                $target->value->args[1] = new Arg(new String_(''));
            }
            $target->value->args[1]->value->value = $value;
            return $this;
        }

        $target->value = $this->makeAstNode($valueType, $value);

        return $this;
    }

    /**
     * Generate an AST node, using `PhpParser` classes, for a value
     *
     * @param string $type
     * @param mixed $value
     * @return ConstFetch|LNumber|String_
     */
    protected function makeAstNode(string $type, $value)
    {
        switch ($type) {
            case 'string':
                return new String_($value);
            case 'boolean':
                return new ConstFetch(new Name($value ? 'true' : 'false'));
            case 'integer':
                return new LNumber($value);
            default:
                throw new \RuntimeException('not implemented replacement type: ' . $type);
        }
    }

    /**
     * Get a referenced var from the `$pointer` array
     *
     * @param array $path
     * @param $pointer
     * @return mixed|null
     */
    protected function seek(array $path, &$pointer)
    {
        $key = array_shift($path);
        foreach ($pointer as $index => &$item) {
            if ($item->key->value === $key) {
                if (!empty($path)) {
                    return $this->seek($path, $item->value->items);
                }

                return $item;
            }
        }

        return null;
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
