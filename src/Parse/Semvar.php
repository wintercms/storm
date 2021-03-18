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

    const OPERATOR_GTE    = 'greaterThanEqual';
    const OPERATOR_GT     = 'greaterThan';
    const OPERATOR_LTE    = 'lessThanEqual';
    const OPERATOR_LT     = 'lessThan';
    const OPERATOR_TILDE  = 'tilde';
    const OPERATOR_CARET  = 'caret';
    const OPERATOR_HYPHEN = 'hyphen';
    const OPERATOR_WILD   = 'wild';

    const OPERATORS = [
        self::OPERATOR_GTE      => '>=',
        self::OPERATOR_GT       => '>',
        self::OPERATOR_LTE      => '<=',
        self::OPERATOR_LT       => '<',
        self::OPERATOR_TILDE    => '~',
        self::OPERATOR_CARET    => '^',
        self::OPERATOR_HYPHEN   => '-',
        self::OPERATOR_WILD     => '*'
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
            'string' => $matches[0] ?? '0.0.0',
            'major'  => (int) ($matches['major'] ?? 0),
            'minor'  => (int) ($matches['minor'] ?? 0),
            'patch'  => (int) ($matches['patch'] ?? 0),
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

        return trim($version);
    }

    /**
     *  Create a version upper and lower range inline with the ~ operator
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
     * Create a version upper and lower range inline with the ^ operator
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
     * Expand a hyphen rule into upper and lower bounds inline with the - operator
     * @param  string $rule     version to be tested, e.g. 1.0 - 2.0
     * @return string           lower and upper bounds, e.g. >=1.0 <2.1
     * @throws \InvalidArgumentException
     */
    public static function expandHyphen(string $rule): string
    {
        $rule = array_filter(array_map(function ($element) {
            return trim($element);
        }, explode(static::OPERATORS[static::OPERATOR_HYPHEN], $rule)), function ($element) {
            return $element !== static::OPERATORS[static::OPERATOR_HYPHEN];
        });

        if (count($rule) !== 2) {
            throw new \InvalidArgumentException('hyphen rule syntactically wrong');
        }

        $upper = static::explode($rule[1]);

        $rule[0] = '>=' . static::makeCompliant($rule[0]);
        $rule[1] = sprintf(
            '<%d.%d.%d',
            $upper['major'],
            ($upper['minor'] === 0 ? 1 : $upper['minor']),
            $upper['patch']
        );

        return implode(' ', $rule);
    }

    /**
     * Expand a wild rule into upper and lower bounds inline with the * operator
     * @param  string $rule     version to be tested, e.g. 1.*
     * @return string           lower and upper bounds, e.g. >=1.0 <2.0
     * @throws \InvalidArgumentException
     */
    public static function expandWild(string $rule): string
    {
        $elements = explode('.', $rule);
        $count = count($elements);

        if ($count === 3) {
            return sprintf(
                '>=%s <%s',
                static::makeCompliant(sprintf('%s.%s', $elements[0], $elements[1])),
                static::makeCompliant(sprintf('%s.%s', $elements[0], ((int) $elements[1]) + 1))
            );
        }

        if ($count === 2) {
            return sprintf(
                '>=%s <%s',
                static::makeCompliant($elements[0]),
                static::makeCompliant(((int) $elements[0]) + 1)
            );
        }

        if ($count === 1 && $elements[0] === '*') {
            return '>=0.0.0 <9999.9999.9999';
        }

        throw new \InvalidArgumentException('wild rule syntactically wrong');
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
            $buffer[$index] = ($buffer[$index] ?? '') . $char;
        }

        return array_map(function ($rule) {
            if (strpos($rule, static::OPERATORS[static::OPERATOR_HYPHEN]) !== false) {
                $rule = static::expandHyphen($rule);
            }
            if (strpos($rule, static::OPERATORS[static::OPERATOR_WILD]) !== false) {
                $rule = static::expandWild($rule);
            }
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
                case static::OPERATOR_TILDE:
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
