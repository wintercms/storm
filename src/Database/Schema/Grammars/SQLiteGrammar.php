<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\SQLiteGrammar as BaseSQLiteGrammar;

class SQLiteGrammar extends BaseSQLiteGrammar
{
    /**
     * Format a value so that it can be used in "default" clauses.
     *
     * @param  mixed  $value
     * @return string
     */
    public function getDefaultValue($value)
    {
        if (is_string($value) && strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'") {
            $value = substr($value, 1, -1);
        }

        return parent::getDefaultValue($value);
    }
}
