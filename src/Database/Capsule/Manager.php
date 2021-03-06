<?php

namespace Winter\Storm\Database\Capsule;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Capsule\Manager as BaseManager;
use Winter\Storm\Database\Connectors\ConnectionFactory;

class Manager extends BaseManager
{
    /**
     * Build the database manager instance.
     *
     * @return void
     */
    protected function setupManager()
    {
        $factory = new ConnectionFactory($this->container);

        $this->manager = new DatabaseManager($this->container, $factory);
    }
}
