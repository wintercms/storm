<?php namespace Winter\Storm\Config;

/**
 * Class ConfigConst
 * @package Winter\Storm\Config
 *
 * This class is for use with ConfigFile as a method to inject a constant into a config file
 */
class ConfigConst
{
    /**
     * @var string function name
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
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
}
