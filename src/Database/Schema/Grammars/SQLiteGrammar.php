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
        if (is_string($value)) {
            $value = preg_replace('#\'#', '', $value);
        }

        return parent::getDefaultValue($value);
    }
}
