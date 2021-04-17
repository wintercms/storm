<?php

use Winter\Storm\Config\Repository;
use Winter\Storm\Config\FileLoader;
use Illuminate\Filesystem\Filesystem;

class ConfigTest extends TestCase
{
    protected $config;

    public function setUp(): void
    {
        $fixture = __DIR__ . '/../fixtures/config';
        $this->config = new Repository(new FileLoader(new Filesystem(), $fixture), 'test');
        $this->config->package('winter.test', $fixture);
    }

    public function testGetFileConfig()
    {
        $this->assertEquals('mysql', $this->config->get('sample-config.default'));
    }

    public function testGetNamespaceConfig()
    {
        $this->assertEquals('bar', $this->config->get('winter.test::foo'));
        $this->assertTrue($this->config->get('winter.test::bar'));
    }

    public function testGetAliasedNamespaceConfig()
    {
        $this->config->registerNamespaceAlias('winter.test', 'winter.alias');
        $this->assertEquals('bar', $this->config->get('winter.alias::foo'));
        $this->assertTrue($this->config->get('winter.alias::bar'));
    }
}
