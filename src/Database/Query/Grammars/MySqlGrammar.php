<?php namespace Winter\Storm\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\MySqlGrammar as BaseMysqlGrammar;
use Winter\Storm\Database\Query\Grammars\Concerns\SelectConcatenations;

class MySqlGrammar extends BaseMysqlGrammar
{
    use SelectConcatenations;
}
