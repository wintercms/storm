<?php namespace Winter\Storm\Parse\PHP;

/**
 * Used with ArrayFile to inject a constant into a PHP array file
 */
class PHPConst
{
    /**
     * @var string function name
     */
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the const name
     */
    public function getName(): string
    {
        return $this->name;
    }
}
