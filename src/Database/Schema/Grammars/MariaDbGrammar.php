<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\MariaDbGrammar as MariaDbGrammarBase;

class MariaDbGrammar extends MariaDbGrammarBase
{
    use Concerns\MySqlBasedGrammar;
}
