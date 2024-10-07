<?php

namespace Winter\Storm\Database\PDO;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Winter\Storm\Database\PDO\Concerns\ConnectsToDatabase;

class MySqlDriver extends AbstractMySQLDriver
{
    use ConnectsToDatabase;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_mysql';
    }
}
