<?php namespace Winter\Storm\Halcyon\Datasource;

use Winter\Storm\Halcyon\Exception\MissingDatasourceException;

class Resolver implements ResolverInterface
{
    /**
     * All of the registered datasources.
     *
     * @var array
     */
    protected array $datasources = [];

    /**
     * The default datasource name.
     */
    protected ?string $default;

    /**
     * Create a new datasource resolver instance.
     *
     * @param array $datasources
     */
    public function __construct(array $datasources = [])
    {
        foreach ($datasources as $name => $datasource) {
            $this->addDatasource($name, $datasource);
        }
    }

    /**
     * @inheritDoc
     */
    public function datasource(string $name = null): DatasourceInterface
    {
        if (is_null($name)) {
            $name = $this->getDefaultDatasource();
        }
        if (!array_key_exists($name, $this->datasources)) {
            throw new MissingDatasourceException(
                sprintf('The Halcyon datasource "%s" does not exist.', $name)
            );
        }

        return $this->datasources[$name];
    }

    /**
     * @inheritDoc
     */
    public function addDatasource(string $name, DatasourceInterface $datasource): void
    {
        $this->datasources[$name] = $datasource;
    }

    /**
     * @inheritDoc
     */
    public function hasDatasource(string $name): bool
    {
        return array_key_exists($name, $this->datasources);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultDatasource(): ?string
    {
        return $this->default ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setDefaultDatasource(string $name): void
    {
        $this->default = $name;
    }
}
