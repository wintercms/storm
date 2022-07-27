<?php namespace Winter\Storm\Halcyon\Datasource;

use \Winter\Storm\Halcyon\Datasource\DatasourceInterface;

/**
 * The resolver interface defines the methods required for resolving Halcyon datasources.
 *
 * @author Winter CMS
 */
interface ResolverInterface
{
    /**
     * Get a datasource instance by name.
     *
     * @throws \Winter\Storm\Halcyon\Exception\MissingDatasourceException If a datasource with the given name does not exist.
     */
    public function datasource(string $name = null): DatasourceInterface;

    /**
     * Adds a datasource to the resolver.
     */
    public function addDatasource(string $name, DatasourceInterface $datasource): void;

    /**
     * Returns if the given datasource name exists.
     */
    public function hasDatasource(string $name): bool;

    /**
     * Gets the default datasource name.
     */
    public function getDefaultDatasource(): ?string;

    /**
     * Sets the default datasource name.
     */
    public function setDefaultDatasource(string $name): void;
}
