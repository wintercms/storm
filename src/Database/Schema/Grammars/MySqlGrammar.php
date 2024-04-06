<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlGrammarBase;

class MySqlGrammar extends MySqlGrammarBase
{
    use Concerns\MySqlBasedGrammar;
}
