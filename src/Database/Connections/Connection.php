<?php namespace Winter\Storm\Database\Connections;

use Illuminate\Database\Connection as ConnectionBase;

/*
 * @deprecated
 */
abstract class Connection extends ConnectionBase
{
    use HasConnection;

    abstract protected function getDoctrineDriver();
}
