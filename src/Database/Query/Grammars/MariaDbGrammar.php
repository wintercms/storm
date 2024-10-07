<?php namespace Winter\Storm\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\MariaDbGrammar as BaseMariaDbGrammer;
use Winter\Storm\Database\Query\Grammars\Concerns\SelectConcatenations;

class MariaDbGrammar extends BaseMariaDbGrammer
{
    use SelectConcatenations;
}
