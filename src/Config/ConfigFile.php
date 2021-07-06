<?php namespace Winter\Storm\Config;

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
class ConfigFile
{
    /**
     * @var null
     */
    protected $ast = null;
    /**
     * @var string|null
     */
    protected $file = null;
    /**
     * @var PrettyPrinterAbstract|WinterPrinter|null
     */
    protected $printer = null;

    /**
     * Config constructor.
     * @param $ast
     * @param PrettyPrinterAbstract|null $printer
     */
    public function __construct($ast, string $file = null, PrettyPrinterAbstract $printer = null)
    {
        if (!($ast[0] instanceof Stmt\Return_)) {
            throw new \InvalidArgumentException('configs must start with a return statement');
        }

        $this->ast = $ast;
        $this->file = $file;
        $this->printer = $printer ?? new WinterPrinter();
    }

    /**
     * @param string $file
     * @return ConfigFile|null
     */
    public static function read(string $file): ?ConfigFile
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException('file not found');
        }

        $content = file_get_contents($file);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        try {
            $ast = $parser->parse($content);
        } catch (Error $e) {
            // should add better handling
            throw new ApplicationException($e);
        }

        return new static($ast, $file);
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function set(): ConfigFile
    {
        $args = func_get_args();

        if (count($args) === 1 && is_array($args[0])) {
            foreach ($args[0] as $key => $value) {
                $this->set($key, $value);
            }

            return $this;
        }

        if (count($args) !== 2 || !is_string($args[0])) {
            throw new \InvalidArgumentException('invalid args passed');
        }

        list($key, $value) = $args;

        $target = $this->seek(explode('.', $key), $this->ast[0]->expr->items);

        $valueType = gettype($value);
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
     * @param string $type
     * @param $value
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
     * @param string|null $filePath
     */
    public function write(string $filePath = null): void
    {
        if (!$filePath && $this->file) {
            $filePath = $this->file;
        }

        file_put_contents($filePath, $this->render());
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return $this->printer->prettyPrintFile($this->ast) . PHP_EOL;
    }

    /**
     * @return Node\Stmt[]|null
     */
    public function getAst()
    {
        return $this->ast;
    }
}
