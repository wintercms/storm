<?php namespace Winter\Storm\Halcyon\Processors;

use Winter\Storm\Parse\Ini;
use Winter\Storm\Support\Str;
use InvalidArgumentException;

/**
 * This class parses CMS object files (pages, partials and layouts).
 * Returns the structured file information.
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class SectionParser
{
    const SECTION_SEPARATOR = '==';

    const ERROR_INI = '_PARSER_ERROR_INI';

    /**
     * Parse the provided content into sections
     */
    protected static function parseIntoSections(string $content, int $limit = 3): array
    {
        $sections = preg_split('/^'.preg_quote(self::SECTION_SEPARATOR).'\s*$/m', $content, -1);

        // If more than the limit sections found, merge the extra sections into the final section
        if ($limit >= 1 && count($sections) > $limit) {
            // Break the content into lines
            $lines = explode(PHP_EOL, $content);
            $seperatorsSeen = 0;

            // Loop over the lines
            foreach ($lines as $number => $line) {
                // If we've seen $limit - 1 separators already then this is now the start of the final section
                if ($seperatorsSeen === ($limit - 1)) {
                    break;
                }

                // Check for a section separator on this line
                if (trim($line) === static::SECTION_SEPARATOR) {
                    $seperatorsSeen++;
                }

                // Remove this line from the result that will be merged into the final section
                unset($lines[$number]);
            }

            // Rebuild the sections array
            $i = 0;
            $originalSections = $sections;
            $sections = [];

            for ($i = 0; $i < ($limit - 1); $i++) {
                $sections[] = $originalSections[$i];
            }
            $sections[] = implode(PHP_EOL, $lines);
        }

        return $sections;
    }

    /**
     * Renders a CMS object as file content.
     * @throws InvalidArgumentException if section separators are found in the settings or code sections
     */
    public static function render(array $data, array $options = []): string
    {
        $sectionOptions = array_merge([
            'wrapCodeInPhpTags' => true,
            'isCompoundObject'  => true,
        ], $options);
        extract($sectionOptions);

        if (!isset($isCompoundObject) || $isCompoundObject === false) {
            return array_get($data, 'content', '');
        }

        // Prepare settings section for saving
        $iniParser = new Ini;
        $trim = function (&$values) use (&$trim) {
            foreach ($values as &$value) {
                if (!is_array($value)) {
                    $value = trim($value);
                }
                else {
                    $trim($value);
                }
            }
        };
        $settings = array_get($data, 'settings', []);
        $trim($settings);
        $settings = $iniParser->render($settings);

        // Prepare code section for saving
        $code = trim(array_get($data, 'code', '') ?? '');
        if ($code) {
            if (isset($wrapCodeInPhpTags) && $wrapCodeInPhpTags === true) {
                $code = preg_replace('/^\<\?php/', '', $code);
                $code = preg_replace('/^\<\?/', '', $code);
                $code = preg_replace('/\?>$/', '', $code);
                $code = trim($code, PHP_EOL);
                $code = '<?php'.PHP_EOL.$code.PHP_EOL.'?>';
            } else {
                $code = $code;
            }
        }

        // Prepare markup section for saving
        $markup = trim(array_get($data, 'markup', ''));

        /*
         * Build content
         *
         * One element = Markup
         * Two elements = Settings, Markup
         * Three Elements = Settings, Code, Markup
         */
        $content = [];
        $sections = 1;

        /**
         * If markup contains a section separator all sections must be present
         * in order to prevent any of the markup content being interpreted as
         * anything else.
         */
        if (count(static::parseIntoSections($markup, 0)) > 1) {
            $sections = 3;
        } else {
            if (!empty($settings)) {
                $sections = 2;
            }
            if (!empty($code)) {
                $sections = 3;
            }
        }

        // Validate the settings section
        if (
            !empty($settings)
            && count(static::parseIntoSections($settings, 0)) > 1
        ) {
            throw new InvalidArgumentException("The settings section cannot be rendered because it contains a section separator");
        }

        // Validate the code section
        if (
            !empty($code)
            && count(static::parseIntoSections($code, 0)) > 1
        ) {
            throw new InvalidArgumentException("The code section cannot be rendered because it contains a section separator");
        }

        switch ($sections) {
            case 1:
                $content[] = $markup;
                break;
            case 2:
                $content[] = $settings;
                $content[] = $markup;
                break;
            case 3:
                $content[] = $settings;
                $content[] = $code;
                $content[] = $markup;
                break;
            default:
                throw new \Exception("Invalid number of sections $sections");
        }

        $content = trim(implode(PHP_EOL . self::SECTION_SEPARATOR . PHP_EOL, $content));

        return $content;
    }

    /**
     * Parses Halcyon section content.
     * The expected file format is following:
     *
     *     INI settings section
     *     ==
     *     PHP code section
     *     ==
     *     Twig markup section
     *
     * If the content has only 2 sections they are parsed as settings and markup.
     * If there is only a single section, it is parsed as markup.
     *
     * Returns an array with the following elements: (array|null) 'settings',
     * (string|null) 'markup', (string|null) 'code'.
     */
    public static function parse(string $content, array $options = []): array
    {
        $sectionOptions = array_merge([
            'isCompoundObject' => true
        ], $options);
        extract($sectionOptions);

        $result = [
            'settings' => [],
            'code'     => null,
            'markup'   => null,
        ];

        if (!isset($isCompoundObject) || $isCompoundObject === false || !strlen($content)) {
            return $result;
        }

        $iniParser = new Ini;
        $sections = static::parseIntoSections($content);
        $count = count($sections);
        foreach ($sections as &$section) {
            $section = trim($section);
        }

        if ($count >= 3) {
            $result['settings'] = @$iniParser->parse($sections[0])
                ?: [self::ERROR_INI => $sections[0]];

            $result['code'] = $sections[1];
            $result['code'] = preg_replace('/^\s*\<\?php/', '', $result['code']);
            $result['code'] = preg_replace('/^\s*\<\?/', '', $result['code']);
            $result['code'] = preg_replace('/\?\>\s*$/', '', $result['code']);
            $result['code'] = trim($result['code'], PHP_EOL);

            $result['markup'] = $sections[2];
        } elseif ($count == 2) {
            $result['settings'] = @$iniParser->parse($sections[0])
                ?: [self::ERROR_INI => $sections[0]];

            $result['markup'] = $sections[1];
        } elseif ($count == 1) {
            $result['markup'] = $sections[0];
        }

        return $result;
    }

    /**
     * Same as parse method, except the line number where the respective section
     * begins is returned.
     *
     * Returns an array with the following elements: (integer|null) 'settings',
     * (integer|null) 'markup', (integer|null) 'code'.
     */
    public static function parseOffset(string $content): array
    {
        $content = Str::normalizeEol($content);
        $sections = static::parseIntoSections($content);
        $count = count($sections);

        $result = [
            'settings' => null,
            'code'     => null,
            'markup'   => null,
        ];

        if ($count >= 3) {
            $result['settings'] = self::adjustLinePosition($content);
            $result['code'] = self::calculateLinePosition($content);
            $result['markup'] = self::calculateLinePosition($content, 2);
        } elseif ($count == 2) {
            $result['settings'] = self::adjustLinePosition($content);
            $result['markup'] = self::calculateLinePosition($content);
        } elseif ($count == 1) {
            $result['markup'] = 1;
        }

        return $result;
    }

    /**
     * Returns the line number of a found instance of CMS object section separator (==).
     * @param string $content Object content
     * @param int $instance Which instance to look for
     * @return int|null The line number the instance was found.
     */
    protected static function calculateLinePosition(string $content, int $instance = 1): ?int
    {
        $count = 0;
        $lines = explode(PHP_EOL, $content);
        foreach ($lines as $number => $line) {
            if (trim($line) == self::SECTION_SEPARATOR) {
                $count++;
            }

            if ($count === $instance) {
                return static::adjustLinePosition($content, $number);
            }
        }

        return null;
    }

    /**
     * Pushes the starting line number forward since it is not always directly
     * after the separator (==). There can be an opening tag or white space in between
     * where the section really begins.
     */
    protected static function adjustLinePosition(string $content, int $startLine = -1): int
    {
        // Account for the separator itself.
        $startLine++;

        $lines = array_slice(explode(PHP_EOL, $content), $startLine);
        foreach ($lines as $line) {
            $line = trim($line);

            /*
             * Empty line
             */
            if ($line == '') {
                $startLine++;
                continue;
            }

            /*
             * PHP line
             */
            if ($line == '<?php' || $line == '<?') {
                $startLine++;
                continue;
            }

            /*
             * PHP namespaced line (use x;) {
             * Don't increase the line count, it will be rewritten by Cms\Classes\CodeParser
             */
            if (preg_match_all('/(use\s+[a-z0-9_\\\\]+;\n?)/mi', $line) == 1) {
                continue;
            }

            break;
        }

        // Line 0 does not exist.
        return ++$startLine;
    }
}
