<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class SoftDeleteCountry extends Country
{
    use \Winter\Storm\Database\Traits\SoftDelete;
}
