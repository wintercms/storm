<?php namespace Winter\Storm\Database\Connections;

use Winter\Storm\Database\Traits\HasConnection;

abstract class Connection extends \Illuminate\Database\Connection
{
    use HasConnection;

    abstract protected function getDoctrineDriver();
}
