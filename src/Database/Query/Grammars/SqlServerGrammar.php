<?php namespace Winter\Storm\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\SqlServerGrammar as BaseSqlServerGrammar;
use Winter\Storm\Database\Query\Grammars\Concerns\SelectConcatenations;

class SqlServerGrammar extends BaseSqlServerGrammar
{
    use SelectConcatenations;
}
