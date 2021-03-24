<?php namespace Winter\Storm\Parse\Processor;

use Winter\Storm\Parse\Processor\Contracts\YamlProcessor;

/**
 * "version.yaml" pre-processor class.
 *
 * Post-v3.x versions of the Symonfy/Yaml package use more recent versions of YAML spec, which breaks common
 * implementations of our version file format. To maintain compatibility, this class will pre-process YAML
 * contents from these files to work with Symfony/Yaml 4.0+.
 *
 * @author Winter CMS
 */
class VersionYamlProcessor implements YamlProcessor
{
    /**
     * @inheritDoc
     */
    public function preprocess($text)
    {
        $lines = preg_split('/[\n\r]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($lines as $num => &$line) {
            // Surround version numbers in quotes if not already done
            $line = preg_replace('/^\s*([\'"]{0})([0-9\.]+)([\'"]{0})\s*:/m', '"$2":', rtrim($line));

            // Add quotes around any unquoted text proceeding a version number
            $line = preg_replace('/^\s*([\'"][0-9\.]+[\'"])\s*: +(?![\'"\s])(.*)/m', '$1: "$2"', $line);

            // If this line is the continuance of a multi-line string, remove the quote from the previous line and
            // continue the quote
            if (
                preg_match('/^(?!\s*(-|([\'"][0-9\.]+[\'"])\s*:))([^\n\r]+)([^"]$)/m', $line)
                && substr($lines[$num - 1], -1) === '"'
            ) {
                $lines[$num - 1] = substr($lines[$num - 1], 0, -1);
                $line .= '"';
            }

            // Add quotes around any unquoted array items
            $line = preg_replace('/^(\s*-\s*)(?![\'" ])(.*)/m', '$1"$2"', $line);
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
