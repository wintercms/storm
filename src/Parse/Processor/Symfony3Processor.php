<?php namespace Winter\Storm\Parse\Processor;

use Winter\Storm\Support\Str;

/**
 * Symfony/Yaml 3 processor.
 *
 * Fixes up YAML syntax that was valid in Symfony/Yaml 3 but no longer valid with Symfony/Yaml 4-6 due to the new YAML
 * spec being adhered to.
 *
 * @author Winter CMS
 */
class Symfony3Processor extends YamlProcessor
{
    /**
     * @inheritDoc
     */
    public function preprocess($text)
    {
        $lines = preg_split('/[\n\r]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($lines as &$line) {
            // Surround array keys with quotes if not already
            $line = preg_replace_callback('/^( *)([\'"]{0}[^\'"\n\r:#]+[\'"]{0})\s*:/m', function ($matches) {
                return $matches[1] . "'" . trim($matches[2]) . "':";
            }, rtrim($line));
        }

        return implode("\n", $lines);
    }

    /**
     * @inheritDoc
     */
    public function process($parsed)
    {
        return $parsed;
    }
}
