<?php namespace Winter\Storm\Database\Connections;

use Illuminate\Database\Connection as BaseConnection;

/*
 * @deprecated
 */
abstract class Connection extends BaseConnection
{
    use HasConnection;

    abstract protected function getDoctrineDriver();
}
