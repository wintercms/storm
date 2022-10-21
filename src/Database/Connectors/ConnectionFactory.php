<?php namespace Winter\Storm\Database\Connectors;

use Illuminate\Database\Connectors\ConnectionFactory as ConnectionFactoryBase;
use Winter\Storm\Database\Connections\Connection;
use Winter\Storm\Database\Connections\MySqlConnection;
use Winter\Storm\Database\Connections\SQLiteConnection;
use Winter\Storm\Database\Connections\PostgresConnection;
use Winter\Storm\Database\Connections\SqlServerConnection;
use InvalidArgumentException;

class ConnectionFactory extends ConnectionFactoryBase
{
    /**
     * Create a new connection instance.
     *
     * @param  string   $driver
     * @param  \PDO     $connection
     * @param  string   $database
     * @param  string   $prefix
     * @param  array    $config
     * @return \Illuminate\Database\Connection
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }

        return match ($driver) {
            'mysql' => new MySqlConnection($connection, $database, $prefix, $config),
            'pgsql' => new PostgresConnection($connection, $database, $prefix, $config),
            'sqlite' => new SQLiteConnection($connection, $database, $prefix, $config),
            'sqlsrv' => new SqlServerConnection($connection, $database, $prefix, $config),
            default => throw new InvalidArgumentException("Unsupported driver [{$driver}]."),
        };
    }
}
