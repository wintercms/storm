<?php namespace Winter\Storm\Parse\Processor\Contracts;

/**
 * Yaml processor contract.
 *
 * Allows for pre-or-post processing of YAML content during parsing or rendering.
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

    /**
     * Pre-process the data that will be rendered to a YAML string or file.
     *
     * @param mixed $data
     * @return mixed
     */
    public function prerender($data);

    /**
     * Post-process a rendered YAML string or file.
     *
     * @param string $yaml
     * @return string
     */
    public function render($yaml);
}
