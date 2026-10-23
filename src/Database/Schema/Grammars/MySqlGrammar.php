<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\MySqlGrammar as BaseMySqlGrammar;

class MySqlGrammar extends BaseMySqlGrammar
{
    use Concerns\MySqlBasedGrammar;
}
