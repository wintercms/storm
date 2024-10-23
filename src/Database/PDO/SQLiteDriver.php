<?php

namespace Winter\Storm\Database\PDO;

use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Winter\Storm\Database\PDO\Concerns\ConnectsToDatabase;

class SQLiteDriver extends AbstractSQLiteDriver
{
    use ConnectsToDatabase;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_sqlite';
    }
}
