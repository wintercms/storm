<?php namespace Winter\Storm\Parse\Processor\Contracts;

/**
 * Yaml processor contract.
 *
 * Allows for pre-or-post processing of YAML content during parsing.
 *
 * @author Winter CMS
 */
interface YamlProcessor
{
    /**
     * Pre-process string content from a YAML string or file.
     *
     * @param string $text
     * @return string
     */
    public function preprocess($text);

    /**
     * Post-process the parsed content from a YAML string or file.
     *
     * @param mixed $parsed
     * @return mixed
     */
    public function process($parsed);
}
