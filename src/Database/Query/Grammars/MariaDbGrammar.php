<?php namespace Winter\Storm\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\MariaDbGrammar as BaseMariaDbGrammar;
use Winter\Storm\Database\Query\Grammars\Concerns\SelectConcatenations;

class MariaDbGrammar extends BaseMariaDbGrammar
{
    use SelectConcatenations;
}
