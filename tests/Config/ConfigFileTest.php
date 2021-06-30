<?php

use Winter\Storm\Config\ConfigFile;

class ConfigFileTest extends TestCase
{
    public function testReadFile()
    {
        $filePath = __DIR__ . '/../fixtures/config/sample-config.php';

        $config = ConfigFile::read($filePath);

        $this->assertInstanceOf(ConfigFile::class, $config);

        $ast = $config->getAst();

        $this->assertTrue(isset($ast[0]->expr->items[0]->key->value));
        $this->assertEquals('debug', $ast[0]->expr->items[0]->key->value);
    }

    public function testWriteFile()
    {
        $filePath = __DIR__ . '/../fixtures/config/sample-config.php';
        $tmpFile = __DIR__ . '/../fixtures/config/temp-config.php';

        $config = ConfigFile::read($filePath);
        $config->write($tmpFile);

        $result = include $tmpFile;
        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('sqlite', $result['connections']);
        $this->assertArrayHasKey('driver', $result['connections']['sqlite']);
        $this->assertEquals('sqlite', $result['connections']['sqlite']['driver']);

        unlink($tmpFile);
    }

    public function testWriteFileWithUpdates()
    {
        $filePath = __DIR__ . '/../fixtures/config/sample-config.php';
        $tmpFile = __DIR__ . '/../fixtures/config/temp-config.php';

        $config = ConfigFile::read($filePath);
        $config->set('connections.sqlite.driver', 'winter');
        $config->write($tmpFile);

        $result = include $tmpFile;
        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('sqlite', $result['connections']);
        $this->assertArrayHasKey('driver', $result['connections']['sqlite']);
        $this->assertEquals('winter', $result['connections']['sqlite']['driver']);

        unlink($tmpFile);
    }

    public function testRender()
    {
        /*
         * Rewrite a single level string
         */
        $config = ConfigFile::read(__DIR__ . '/../fixtures/config/sample-config.php');
        $config->set('url', 'https://wintercms.com');
        $result = eval('?>' . $config->render());

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('https://wintercms.com', $result['url']);

        /*
         * Rewrite a second level string
         */
        $config = ConfigFile::read(__DIR__ . '/../fixtures/config/sample-config.php');
        $config->set('memcached.host', '69.69.69.69');
        $result = eval('?>' . $config->render());

        $this->assertArrayHasKey('memcached', $result);
        $this->assertArrayHasKey('host', $result['memcached']);
        $this->assertEquals('69.69.69.69', $result['memcached']['host']);

        /*
         * Rewrite a third level string
         */
        $config = ConfigFile::read(__DIR__ . '/../fixtures/config/sample-config.php');
        $config->set('connections.mysql.host', '127.0.0.1');
        $result = eval('?>' . $config->render());

        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('mysql', $result['connections']);
        $this->assertArrayHasKey('host', $result['connections']['mysql']);
        $this->assertEquals('127.0.0.1', $result['connections']['mysql']['host']);

        /*
         * Test alternative quoting
         */
        $config = ConfigFile::read(__DIR__ . '/../fixtures/config/sample-config.php');
        $config->set('timezone', 'The Fifth Dimension');
        $config->set('timezoneAgain', 'The "Sixth" Dimension');
        $result = eval('?>' . $config->render());

        $this->assertArrayHasKey('timezone', $result);
        $this->assertArrayHasKey('timezoneAgain', $result);
        $this->assertEquals('The Fifth Dimension', $result['timezone']);
        $this->assertEquals('The "Sixth" Dimension', $result['timezoneAgain']);

        /*
         * Rewrite a boolean
         */
        $config = ConfigFile::read(__DIR__ . '/../fixtures/config/sample-config.php');
        $config->set('debug', false)
            ->set('debugAgain', true)
            ->set('bullyIan', true)
            ->set('booLeeIan', false)
            ->set('memcached.weight', false)
            ->set('connections.pgsql.password', true);

        $result = eval('?>' . $config->render());
        
        $this->assertArrayHasKey('debug', $result);
        $this->assertArrayHasKey('debugAgain', $result);
        $this->assertArrayHasKey('bullyIan', $result);
        $this->assertArrayHasKey('booLeeIan', $result);
        $this->assertFalse($result['debug']);
        $this->assertTrue($result['debugAgain']);
        $this->assertTrue($result['bullyIan']);
        $this->assertFalse($result['booLeeIan']);

        $this->assertArrayHasKey('memcached', $result);
        $this->assertArrayHasKey('weight', $result['memcached']);
        $this->assertFalse($result['memcached']['weight']);

        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('pgsql', $result['connections']);
        $this->assertArrayHasKey('password', $result['connections']['pgsql']);
        $this->assertTrue($result['connections']['pgsql']['password']);
        $this->assertEquals('', $result['connections']['sqlsrv']['password']);

        /*
         * Rewrite an integer
         */
        $config = ConfigFile::read(__DIR__ . '/../fixtures/config/sample-config.php');
        $config->set('aNumber', 69);
        $result = eval('?>' . $config->render());

        $this->assertArrayHasKey('aNumber', $result);
        $this->assertEquals(69, $result['aNumber']);
    }
}
