<?php

namespace Winter\Storm\Database\PDO;

use Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use Winter\Storm\Database\PDO\Concerns\ConnectsToDatabase;

class PostgresDriver extends AbstractPostgreSQLDriver
{
    use ConnectsToDatabase;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_pgsql';
    }
}
