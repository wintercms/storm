<?php namespace Winter\Storm\Config;

use PhpParser\Error;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
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
            throw new Error($e);
        }

        return new static($ast, $file);
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function set(string $key, $value): ConfigFile
    {
        $target = $this->seek(explode('.', $key), $this->ast[0]->expr->items);

        switch (get_class($target->value)) {
            case String_::class:
                $target->value->value = $value;
                break;
            case FuncCall::class:
                if ($target->value->name->parts[0] !== 'env' || !isset($target->value->args[0])) {
                    break;
                }
                if (isset($target->value->args[0]) && !isset($target->value->args[1])) {
                    $target->value->args[1] = clone $target->value->args[0];
                }
                $target->value->args[1]->value->value = $value;
                break;
        }

        return $this;
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
}
