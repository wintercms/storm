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
     * @throws ApplicationException If no file, or a malformed SVG, is found at the given path.
     */
    public static function extract(string $path, bool $minify = true): string
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
     */
    public static function sanitize(string $svg, bool $minify = true): string
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
