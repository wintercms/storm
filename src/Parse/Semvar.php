<?php namespace Winter\Storm\Parse;

/**
 * Semvar parser to allow for version matching against Composer
 * style rules
 *
 * @author Jack Wilkinson
 */

class Semvar
{
    const LOGICAL_OR = '||';

    const OPERATOR_GTE   = 'greaterThanEqual';
    const OPERATOR_GT    = 'greaterThan';
    const OPERATOR_LTE   = 'lessThanEqual';
    const OPERATOR_LT    = 'lessThan';
    const OPERATOR_TILDY = 'tildy';
    const OPERATOR_CARET = 'caret';

    const OPERATORS = [
        self::OPERATOR_GTE      => '>=',
        self::OPERATOR_GT       => '>',
        self::OPERATOR_LTE      => '<=',
        self::OPERATOR_LT       => '<',
        self::OPERATOR_TILDY    => '~',
        self::OPERATOR_CARET    => '^'
    ];

    const SEMVAR_REGEX = '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>'
        . '(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P'
        . '<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    /**
     * Match a composer style version rule against a semvar version
     * @param  string $rule    version rule, e.g. `>=1.2 <1.3 || >=1.5`
     * @param  string $version version to be tested, e.g. 1.0.1
     * @return bool            if the version matches the rule
     */
    public static function match(string $rule, string $version): bool
    {
        foreach (static::compileRuleset($rule) as $rule) {
            if (static::compareRuleset($rule, $version)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Explode a version into key value pairings by meaning
     * @param  string $version version to be expanded, e.g. 1.0.1
     * @return array           key value paring of the expanded version
     */
    public static function explode(string $version): array
    {
        preg_match(static::SEMVAR_REGEX, static::makeCompliant($version), $matches);
        return [
            'string' => $matches[0],
            'major'  => (int) $matches['major'],
            'minor'  => (int) $matches['minor'],
            'patch'  => (int) $matches['patch'],
        ];
    }

    /**
     * Make a semvar version compliant by expanding it if needed
     * @param  string $version version to be tested, e.g. 1.0
     * @return string          fully expanded version, e.g. 1.0.0
     */
    public static function makeCompliant(string $version): string
    {
        $points = substr_count($version, '.');

        if ($points > 3) {
            throw new \InvalidArgumentException('Version has too much precision');
        }

        if ($points === 3) {
            return $version;
        }

        for ($i = 2; $i != $points; $i--) {
            $version .= '.0';
        }

        return $version;
    }

    /**
     * Expand a version into upper and lower bounds inline with the ~ operator
     * @param  string $version version to be tested, e.g. 1.2.3
     * @return array           lower and upper bounds, e.g. [1.2.3, 1.3.0]
     */
    public static function tildyRange(string $version): array
    {
        $version = static::explode($version);

        if ($version['minor'] === 0 && $version['patch'] === 0) {
            return [$version['string'], sprintf('%d.0.0', $version['major'] + 1)];
        }

        if ($version['patch'] === 0) {
            return [$version['string'], sprintf('%d.%d.0', $version['major'], $version['minor'] + 1)];
        }

        return [$version['string'], sprintf('%d.%d.%d', $version['major'], $version['minor'], $version['patch'] + 1)];
    }

    /**
     * Expand a version into upper and lower bounds inline with the ^ operator
     * @param  string $version version to be tested, e.g. 1.2.3
     * @return array           lower and upper bounds, e.g. [1.2.3, 2.0.0]
     */
    public static function caretRange(string $version): array
    {
        $version = static::explode($version);

        if ($version['major'] === 0) {
            return [$version['string'], sprintf('0.%d.0', $version['minor'] + 1)];
        }

        return [$version['string'], sprintf('%d.0.0', $version['major'] + 1)];
    }

    /**
     * Converts a rule into individual test cases
     * @param  string $rule version rule, e.g. `>=1.2 <1.3 || >=1.5`
     * @return array        list of test cases
     */
    protected static function compileRuleset(string $rule): array
    {
        $buffer = [];
        $index = 0;
        $last = null;

        foreach (str_split($rule) as $pos => $char) {
            if ($last === null) {
                $last = $char;
                continue;
            }

            if ($last === substr(static::LOGICAL_OR, 0, 1) && $char === substr(static::LOGICAL_OR, 0, 1)) {
                $last = null;
                $index++;
                continue;
            }

            $buffer[$index] = ($buffer[$index] ?? '') . $last;
            $last = $char;
        }

        if (isset($char)) {
            $buffer[$index] .= $char;
        }

        return array_map(function ($rule) {
            return explode(' ', trim($rule));
        }, $buffer);
    }

    /**
     * Compare a ruleset (list of test cases) against a version
     * @param  array  $rule    ruleset generated by `self::compileRuleset()`
     * @param  string $version version rule, e.g. `>=1.2 <1.3 || >=1.5`
     * @return bool            if the version matches the ruleset or not
     */
    protected static function compareRuleset(array $rule, string $version): bool
    {
        $version = static::makeCompliant($version);

        foreach ($rule as $item) {
            $operator = null;
            $compare = '';
            foreach (static::OPERATORS as $name => $symbol) {
                if (strpos($item, $symbol) !== false) {
                    $operator = $name;
                    $compare = static::makeCompliant(str_replace($symbol, '', $item));
                    break;
                }
            }

            if (!$operator && static::makeCompliant($compare ? $compare : $item) !== $version) {
                return false;
            }

            switch ($operator) {
                case static::OPERATOR_GTE:
                    if (version_compare($version, $compare) === -1) {
                        return false;
                    }
                    break;
                case static::OPERATOR_GT:
                    if (version_compare($version, $compare) !== 1) {
                        return false;
                    }
                    break;
                case static::OPERATOR_LTE:
                    if (version_compare($version, $compare) === 1) {
                        return false;
                    }
                    break;
                case static::OPERATOR_LT:
                    if (version_compare($version, $compare) !== -1) {
                        return false;
                    }
                    break;
                case static::OPERATOR_TILDY:
                    list($lower, $upper) = static::tildyRange($compare);
                    if (!(version_compare($version, $lower) >= 0 && version_compare($version, $upper) <= 0)) {
                        return false;
                    }
                    break;
                case static::OPERATOR_CARET:
                    list($lower, $upper) = static::caretRange($compare);
                    if (
                        !(version_compare($version, $lower) >= 0 && version_compare($version, $upper) <= 0)
                        && !($version === $lower || $version === $upper)
                    ) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }
}
