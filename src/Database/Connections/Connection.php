<?php namespace Winter\Storm\Database\Connections;

use Illuminate\Database\Connection as ConnectionBase;
use Winter\Storm\Database\Traits\HasConnection;

/*
 * @deprecated
 */
abstract class Connection extends ConnectionBase
{
    use HasConnection;

    abstract protected function getDoctrineDriver();
}
