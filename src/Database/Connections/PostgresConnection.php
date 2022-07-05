<?php namespace Winter\Storm\Database\Connections;

use Illuminate\Database\Schema\PostgresBuilder;
use Illuminate\Database\PDO\PostgresDriver;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Winter\Storm\Database\Query\Grammars\PostgresGrammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar as SchemaGrammar;

/**
 * @phpstan-property \Illuminate\Database\Schema\Grammars\Grammar|null $schemaGrammar
 */
class PostgresConnection extends Connection
{
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
     * @return \Illuminate\Database\Schema\PostgresBuilder
     */
    public function getSchemaBuilder()
    {
        if (!isset($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new PostgresBuilder($this);
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
     * @return \Illuminate\Database\Query\Processors\PostgresProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new PostgresProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Illuminate\Database\PDO\PostgresDriver
     */
    protected function getDoctrineDriver()
    {
        return new PostgresDriver;
    }
}
