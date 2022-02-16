<?php namespace Winter\Storm\Parse\PHP;

/**
 * Used with ArrayFile to inject a function call into a PHP array file
 */
class PHPFunction
{
    /**
     * @var string function name
     */
    protected $name;
    /**
     * @var array function arguments
     */
    protected $args;

    /**
     * @param string $name
     * @param array $args
     */
    public function __construct(string $name, array $args = [])
    {
        $this->name = $name;
        $this->args = $args;
    }

    /**
     * Get the function name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the function arguments
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }
}
