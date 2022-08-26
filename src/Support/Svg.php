<?php namespace Winter\Storm\Support;

use Winter\Storm\Exception\ApplicationException;
use enshrined\svgSanitize\Sanitizer;

/**
 * A simple wrapper for SVG extraction.
 *
 * Under the hood, this uses the "svg-sanitizer" library by Daryll Doyle (https://github.com/darylldoyle/svg-sanitizer)
 * to sanitize and/or extract the SVG for usage within Winter.
 *
 * @author Winter CMS
 */
class Svg
{
    /**
     * Extracts and sanitizes SVG code from a given file.
     *
     * @param string $path The path to the SVG file.
     * @param boolean $minify Whether to minify the extracted SVG code.
     * @return string
     * @throws ApplicationException If no file, or a malformed SVG, is found at the given path.
     */
    public static function extract($path, $minify = true)
    {
        if (!is_file($path)) {
            throw new ApplicationException(sprintf('No SVG file found at path "%s"', $path));
        }

        $sanitized = static::sanitize(file_get_contents($path), $minify);

        if (!$sanitized) {
            throw new ApplicationException(sprintf('Malformed SVG encountered at path "%s"', $path));
        }

        return $sanitized;
    }

    /**
     * Sanitizes SVG code.
     *
     * See https://github.com/darylldoyle/svg-sanitizer for usage of the underlying sanitization library.
     *
     * @param string $svg SVG code.
     * @param boolean $minify Whether to minify the given SVG code.
     * @return string
     */
    protected static function sanitize($svg, $minify = true)
    {
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);
        $sanitizer->removeXMLTag(true);

        if ($minify) {
            $sanitizer->minify(true);
        }

        return trim($sanitizer->sanitize($svg));
    }
}
