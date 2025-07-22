<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\MariaDbGrammar as BaseMariaDbGrammar;

class MariaDbGrammar extends BaseMariaDbGrammar
{
    use Concerns\MySqlBasedGrammar;
}
