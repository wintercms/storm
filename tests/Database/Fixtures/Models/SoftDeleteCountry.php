<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

class SoftDeleteCountry extends Country
{
    use \Winter\Storm\Database\Traits\SoftDelete;
}
