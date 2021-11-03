<?php namespace Winter\Storm\Parse\Processor;

use Winter\Storm\Parse\Processor\Contracts\YamlProcessor as YamlProcessorContract;

/**
 * YAML processor abstract.
 *
 * Provides base functionality for YAML processors, so that extended classes only need to overwrite the methods
 * that they intend to actually use.
 *
 * @author Winter CMS
 */
abstract class YamlProcessor implements YamlProcessorContract
{
    /**
     * @inheritDoc
     */
    public function preprocess($text)
    {
        return $text;
    }

    /**
     * @inheritDoc
     */
    public function process($parsed)
    {
        return $parsed;
    }

    /**
     * @inheritDoc
     */
    public function prerender($data)
    {
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function render($yaml)
    {
        return $yaml;
    }
}
