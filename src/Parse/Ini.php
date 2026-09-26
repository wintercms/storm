<?php namespace Winter\Storm\Parse;

/**
 * Initialization (INI) configuration parser that uses "Winter flavoured INI",
 * with the following improvements:
 *
 * - Parsing supports infinite array nesting
 * - Ability to render INI from a PHP array
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class Ini
{
    /**
     * Placeholder used to neutralize PHP's `${VAR}` environment-variable
     * interpolation in parse_ini_string(). The `${` token is replaced with
     * this marker before parsing and restored afterwards, preventing the
     * information-disclosure vulnerability tracked as CVE-2026-25125 while
     * preserving all other INI semantics (escape sequences, multi-line
     * quoted values, boolean coercion, etc.).
     */
    protected const DOLLAR_BRACE_PLACEHOLDER = '__WNTR_INI_DOLLAR_BRACE_ESCAPE_7f8c9b2a__';

    /**
     * Placeholder used to keep tokens in an INI document from being
     * substituted by parse_ini_string(), which replaces any unquoted token
     * matching the name of a defined PHP constant with that constant's value.
     * The marker is prefixed to each such token before parsing and removed
     * again afterwards, so a document's own text is returned as written while
     * every other INI semantic (escape sequences, multi-line quoted values,
     * keyword coercion, etc.) is preserved.
     */
    protected const IDENTIFIER_PLACEHOLDER = '__WNTR_INI_IDENTIFIER_ESCAPE_5d1e0a63__';

    /**
     * Pattern matching a single INI identifier token, i.e. the shape that
     * parse_ini_string() will look up in the table of defined PHP constants.
     */
    protected const IDENTIFIER_PATTERN = '[A-Za-z_][A-Za-z0-9_]*';

    /**
     * Tokens that parse_ini_string() coerces to a boolean-ish value and which
     * are therefore left untouched by the identifier pre-pass. The comparison
     * is case-insensitive, matching the native scanner.
     */
    protected const RESERVED_KEYWORDS = [
        'true',
        'false',
        'on',
        'off',
        'yes',
        'no',
        'none',
        'null',
    ];

    /**
     * The reserved keywords that parse_ini_string() coerces in value position
     * but resolves against the table of defined PHP constants when they appear
     * as a bracketed key offset (`a[none] = x`). Only there do they need the
     * same treatment as any other token. `true`, `false` and `null` are absent
     * because the scanner coerces them in both positions.
     */
    protected const OFFSET_RESOLVED_KEYWORDS = [
        'on',
        'off',
        'yes',
        'no',
        'none',
    ];

    /**
     * Parses supplied INI contents in to a PHP array.
     * @param string $contents INI contents to parse.
     * @return array
     */
    public function parse($contents)
    {
        $contents = $this->parsePreProcess($contents);
        // Neutralize tokens that PHP's native parser would otherwise replace
        // with the value of a same-named PHP constant.
        $contents = $this->escapeIdentifiers($contents);
        // Neutralize `${...}` env-var interpolation before handing the content
        // to PHP's native parser. See CVE-2026-25125.
        $contents = str_replace('${', static::DOLLAR_BRACE_PLACEHOLDER, $contents);
        $contents = parse_ini_string($contents, true);
        $contents = $this->restoreDollarBrace($contents);
        $contents = $this->restorePlaceholders($contents);
        $contents = $this->parsePostProcess($contents);
        return $contents;
    }

    /**
     * Prefixes a marker to every token in the document that names a currently
     * defined PHP constant, so that parse_ini_string() cannot resolve it. The
     * marker is removed again by restorePlaceholders() once parsing is done,
     * leaving the document's own text.
     *
     * A token is only marked when defined() reports a constant of that name,
     * because parse_ini_string() leaves every other token as written; that
     * keeps the document handed to the native parser byte-identical whenever
     * it names no constant at all. The whole document is scanned rather than
     * only the value side of each line, because a constant named inside a
     * bracketed key (`a[SOME_NAME] = x`) is substituted as well. Marking a
     * token that the native parser would not have resolved - in a quoted run,
     * a comment, a key or a section header - is harmless, since the marker is
     * stripped again either way.
     *
     * Marking happens per whole matched token rather than by substituting the
     * token everywhere it occurs in the document, so that a short constant name
     * cannot have a marker inserted into the middle of a longer word. That
     * distinction matters for the reserved keywords, whose coercion the native
     * scanner decides from the value's exact text: a marker landing inside one
     * of them would stop it coercing. They are otherwise skipped so that their
     * coercion to `1`/`''` is unchanged, except as a bracketed key offset,
     * which is the one position where the scanner resolves them as constants
     * instead of coercing them.
     *
     * @param string $contents
     * @return string
     */
    protected function escapeIdentifiers($contents)
    {
        // Double any marker the document already contains, so that
        // restorePlaceholders() can tell it apart from one inserted here.
        $contents = str_replace(
            static::IDENTIFIER_PLACEHOLDER,
            static::IDENTIFIER_PLACEHOLDER . static::IDENTIFIER_PLACEHOLDER,
            $contents
        );

        // The first branch matches a token that stands alone between the
        // delimiters of a bracketed key offset, together with the delimiter and
        // any horizontal whitespace in front of it, which the native scanner
        // skips when it resolves the offset. The second matches any other token.
        // Both consume the whole token, so the marker is only ever inserted in
        // front of one.
        $pattern = '/(?<lead>[\[|][ \t]*)(?<offset>' . static::IDENTIFIER_PATTERN . ')(?=[ \t]*[\]|])|'
            . static::IDENTIFIER_PATTERN . '/';

        $defined = [];

        $escaped = $this->replaceIdentifiers($pattern, function ($match) use (&$defined) {
            $lead = $match['lead'] ?? '';
            $isOffset = ($match['offset'] ?? '') !== '';
            $token = $isOffset ? $match['offset'] : $match[0];

            if (!array_key_exists($token, $defined)) {
                $defined[$token] = defined($token);
            }

            if (!$defined[$token]) {
                return $lead . $token;
            }

            $keyword = strtolower($token);

            if (
                in_array($keyword, static::RESERVED_KEYWORDS, true) &&
                !($isOffset && in_array($keyword, static::OFFSET_RESOLVED_KEYWORDS, true))
            ) {
                return $lead . $token;
            }

            return $lead . static::IDENTIFIER_PLACEHOLDER . $token;
        }, $contents);

        // A failed pre-pass must not be mistaken for a document that needed no
        // marking: the contents would reach the native parser unprotected, or
        // as null. preg_replace_callback() returns null on a PCRE failure, the
        // reachable one being a backtrack or recursion limit.
        if ($escaped === null) {
            throw new \RuntimeException(sprintf(
                'Unable to parse INI contents: %s',
                preg_last_error_msg()
            ));
        }

        return $escaped;
    }

    /**
     * Runs the identifier pre-pass over the document contents.
     *
     * Separated from escapeIdentifiers() so that its failure path can be exercised: PCRE only
     * fails here on a backtrack or recursion limit, and how close to those limits a given
     * subject lands differs between PCRE builds.
     *
     * @return string|null Null if the replacement failed.
     */
    protected function replaceIdentifiers(string $pattern, callable $callback, string $contents): ?string
    {
        return preg_replace_callback($pattern, $callback, $contents);
    }

    /**
     * Recursively removes the identifier marker inserted by
     * escapeIdentifiers() wherever it appears in the parsed result, restoring
     * a doubled marker to the single one the document itself contained. Values
     * inside section arrays are handled too, and so are keys.
     *
     * The `${` token is restored separately, by restoreDollarBrace(), which
     * parse() still calls with the whole parsed array exactly as it did before
     * this pre-pass existed, so that an override of that method keeps seeing
     * every node it used to.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function restorePlaceholders($value)
    {
        if (is_array($value)) {
            $restored = [];
            foreach ($value as $key => $item) {
                if (is_string($key)) {
                    $key = $this->restorePlaceholders($key);
                }
                $restored[$key] = $this->restorePlaceholders($item);
            }
            return $restored;
        }

        if (is_string($value) && strpos($value, static::IDENTIFIER_PLACEHOLDER) !== false) {
            return strtr($value, [
                static::IDENTIFIER_PLACEHOLDER . static::IDENTIFIER_PLACEHOLDER => static::IDENTIFIER_PLACEHOLDER,
                static::IDENTIFIER_PLACEHOLDER => '',
            ]);
        }

        return $value;
    }

    /**
     * Recursively restores the `${` token wherever the placeholder appears
     * in the parsed result. Values inside section arrays are handled too.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function restoreDollarBrace($value)
    {
        if (is_array($value)) {
            $restored = [];
            foreach ($value as $key => $item) {
                if (is_string($key)) {
                    $key = str_replace(static::DOLLAR_BRACE_PLACEHOLDER, '${', $key);
                }
                $restored[$key] = $this->restoreDollarBrace($item);
            }
            return $restored;
        }

        if (is_string($value)) {
            return str_replace(static::DOLLAR_BRACE_PLACEHOLDER, '${', $value);
        }

        return $value;
    }

    /**
     * Parses supplied INI file contents in to a PHP array.
     * @param string $fileName File to read contents and parse.
     * @return array
     */
    public function parseFile($fileName)
    {
        $contents = file_get_contents($fileName);
        return $this->parse($contents);
    }

    /**
     * This method converts key names traditionally invalid, "][", and
     * replaces them with a valid character "|" so parse_ini_string
     * can function correctly. It also forces arrays to have unique
     * indexes so their integrity is maintained.
     * @param string $contents INI contents to parse.
     * @return string
     */
    protected function parsePreProcess($contents)
    {
        $contents = preg_replace('~\R~u', PHP_EOL, $contents); // Normalize EOL
        $contents = explode(PHP_EOL, $contents);
        $count = 0;
        $lastName = null;

        foreach ($contents as $key => $content) {
            if (strpos($content, '=') === false) {
                continue;
            }

            $parts = explode('=', $content, 2);
            if (count($parts) < 2) {
                continue;
            }

            $varName = $parts[0];
            if ($lastName != $varName) {
                $count = 0;
                $lastName = null;
            }

            if (
                ($lastName === null || $lastName == $varName) &&
                strpos($varName, '[]') !== false
            ) {
                $varName = str_replace('[]', '['.$count.']', $varName);
                $count++;
            }

            $lastName = $parts[0];
            $parts[0] = str_replace('][', '|', $varName);
            $contents[$key] = implode('=', $parts);
        }

        return implode(PHP_EOL, $contents);
    }

    /**
     * This method takes the valid key name from pre processing and
     * converts it back to a real PHP array. Eg:
     * - name[validation|regex|message]
     * Converts to:
     * - name => [validation => [regex => [message]]]
     * @param array $array
     * @return array
     */
    protected function parsePostProcess($array)
    {
        $result = [];

        foreach ($array as $key => $value) {
            $this->expandProperty($result, $key, $value);

            if (is_array($value)) {
                $result[$key] = $this->parsePostProcess($value);
            }
        }

        return $result;
    }

    /**
     * Expands a single array property from traditional INI syntax.
     * If no key is given to the method, the entire array will be replaced.
     * @param  array   $array
     * @param  string|null  $key
     * @param  mixed   $value
     * @return array
     */
    public function expandProperty(array &$array, $key = null, $value = null)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('|', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array =& $array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Formats an INI file string from an array
     * @param array $vars Data to format.
     * @param int $level Specifies the level of array value.
     * @return string Returns the INI file string.
     */
    public function render($vars = [], $level = 1)
    {
        $content = '';
        $sections = [];

        foreach ($vars as $key => $value) {
            if (is_array($value)) {
                if ($this->isFinalArray($value)) {
                    foreach ($value as $_value) {
                        $content .= $key.'[] = '.$this->evalValue($_value).PHP_EOL;
                    }
                }
                else {
                    $sections[$key] = $this->renderProperties($value);
                }
            }
            elseif (strlen($value)) {
                $content .= $key.' = '.$this->evalValue($value).PHP_EOL;
            }
        }

        foreach ($sections as $key => $section) {
            $content .= PHP_EOL.'['.$key.']'.PHP_EOL.$section;
        }

        return trim($content);
    }

    /**
     * Renders section properties.
     * @param array $vars
     * @return string
     */
    protected function renderProperties($vars = [])
    {
        $content = '';

        foreach ($vars as $key => $value) {
            if (is_array($value)) {
                if ($this->isFinalArray($value)) {
                    foreach ($value as $_value) {
                        $content .= $key.'[] = '.$this->evalValue($_value).PHP_EOL;
                    }
                }
                else {
                    $value = $this->flattenProperties($value);
                    foreach ($value as $_key => $_value) {
                        if (is_array($_value)) {
                            foreach ($_value as $__value) {
                                $content .= $key.'['.$_key.'][] = '.$this->evalValue($__value).PHP_EOL;
                            }
                        }
                        else {
                            $content .= $key.'['.$_key.'] = '.$this->evalValue($_value).PHP_EOL;
                        }
                    }
                }
            }
            elseif (strlen($value)) {
                $content .= $key.' = '.$this->evalValue($value).PHP_EOL;
            }
        }

        return $content;
    }

    /**
     * Flatten a multi-dimensional associative array for traditional INI syntax.
     * @param  array   $array
     * @param  string  $prepend
     * @return array
     */
    protected function flattenProperties($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if ($this->isFinalArray($value)) {
                    $results[$prepend.$key] = $value;
                }
                else {
                    $results = array_merge($results, $this->flattenProperties($value, $prepend.$key.']['));
                }
            }
            else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Converts a PHP value to make it suitable for INI format.
     * Strings are escaped.
     * @param string $value Specifies the value to process
     * @return string Returns the processed value
     */
    protected function evalValue($value)
    {
        // Numeric
        if (is_numeric($value)) {
            return $value;
        }

        // String (default)
        $value = str_replace('"', '\"', $value);
        $value = preg_replace('~\\\"([\r\n])~', '\\\"""$1', $value);

        return '"'.$value.'"';
    }

    /**
     * Checks if the array is the final node in a multidimensional array.
     * Checked supplied array is not associative and contains no array values.
     * @param array $array
     * @return bool
     */
    protected function isFinalArray(array $array)
    {
        return !empty($array) &&
            !count(array_filter($array, 'is_array')) &&
            !count(array_filter(array_keys($array), 'is_string'));
    }
}
