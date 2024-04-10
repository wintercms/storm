<?php namespace Winter\Storm\Database\Connections;

use Illuminate\Database\Schema\SQLiteBuilder;
use Illuminate\Database\Query\Processors\SQLiteProcessor;

use Winter\Storm\Database\PDO\SQLiteDriver;
use Winter\Storm\Database\Query\Grammars\SQLiteGrammar as QueryGrammar;
use Winter\Storm\Database\Schema\Grammars\SQLiteGrammar as SchemaGrammar;

/**
 * @phpstan-property \Illuminate\Database\Schema\Grammars\Grammar|null $schemaGrammar
 */
class SQLiteConnection extends \Illuminate\Database\SQLiteConnection
{
    use Connection;

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\SQLiteBuilder
     */
    public function getSchemaBuilder()
    {
        if (!isset($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SQLiteBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\SQLiteProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new SQLiteProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Winter\Storm\Database\PDO\SQLiteDriver
     */
    protected function getDoctrineDriver()
    {
        return new SQLiteDriver;
    }
}
