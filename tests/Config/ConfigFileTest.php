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

    public function testWriteFileWithUpdatesArray()
    {
        $filePath = __DIR__ . '/../fixtures/config/sample-config.php';
        $tmpFile = __DIR__ . '/../fixtures/config/temp-config.php';

        $config = ConfigFile::read($filePath);
        $config->set([
            'connections.sqlite.driver' => 'winter',
            'connections.sqlite.prefix' => 'test',
        ]);
        $config->write($tmpFile);

        $result = include $tmpFile;
        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('sqlite', $result['connections']);
        $this->assertArrayHasKey('driver', $result['connections']['sqlite']);
        $this->assertEquals('winter', $result['connections']['sqlite']['driver']);
        $this->assertEquals('test', $result['connections']['sqlite']['prefix']);

        unlink($tmpFile);
    }

    public function testWriteEnvUpdates()
    {
        $filePath = __DIR__ . '/../fixtures/config/env-config.php';
        $tmpFile = __DIR__ . '/../fixtures/config/temp-config.php';

        $config = ConfigFile::read($filePath);
        $config->write($tmpFile);

        $result = include $tmpFile;

        $this->assertArrayHasKey('sample', $result);
        $this->assertArrayHasKey('value', $result['sample']);
        $this->assertArrayHasKey('no_default', $result['sample']);
        $this->assertEquals('default', $result['sample']['value']);
        $this->assertNull($result['sample']['no_default']);

        $config->set([
            'sample.value' => 'winter',
            'sample.no_default' => 'test',
        ]);
        $config->write($tmpFile);

        $result = include $tmpFile;

        $this->assertArrayHasKey('sample', $result);
        $this->assertArrayHasKey('value', $result['sample']);
        $this->assertArrayHasKey('no_default', $result['sample']);
        $this->assertEquals('winter', $result['sample']['value']);
        $this->assertEquals('test', $result['sample']['no_default']);

        unlink($tmpFile);
    }

    public function testCasting()
    {
        $config = ConfigFile::read(__DIR__ . '/../fixtures/config/sample-config.php');
        $result = eval('?>' . $config->render());

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('http://localhost', $result['url']);

        $config = ConfigFile::read(__DIR__ . '/../fixtures/config/sample-config.php');
        $config->set('url', false);
        $result = eval('?>' . $config->render());

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertFalse($result['url']);

        $config = ConfigFile::read(__DIR__ . '/../fixtures/config/sample-config.php');
        $config->set('url', 1234);
        $result = eval('?>' . $config->render());

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertIsInt($result['url']);
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

        /*un-
         * Test alternative quoting
         */
        $config = ConfigFile::read(__DIR__ . '/../fixtures/config/sample-config.php');
        $config->set('timezone', 'The Fifth Dimension')
            ->set('timezoneAgain', 'The "Sixth" Dimension');
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

    public function testReadCreateFile()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';

        $this->assertFalse(file_exists($file));

        $config = ConfigFile::read($file, true);

        $this->assertInstanceOf(ConfigFile::class, $config);

        $config->write();

        $this->assertTrue(file_exists($file));
        $this->assertEquals(sprintf('<?php%1$s%1$sreturn [];%1$s', PHP_EOL), file_get_contents($file));

        unlink($file);
    }

    public function testWriteDotNotation()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);
        $config->set('w.i.n.t.e.r', 'cms');

        $result = eval('?>' . $config->render());

        $this->assertArrayHasKey('w', $result);
        $this->assertArrayHasKey('i', $result['w']);
        $this->assertArrayHasKey('n', $result['w']['i']);
        $this->assertArrayHasKey('t', $result['w']['i']['n']);
        $this->assertArrayHasKey('e', $result['w']['i']['n']['t']);
        $this->assertArrayHasKey('r', $result['w']['i']['n']['t']['e']);
        $this->assertEquals('cms', $result['w']['i']['n']['t']['e']['r']);
    }

    public function testWriteDotNotationMixedCase()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);
        $config->set('w.0.n.1.e.2', 'cms');

        $result = eval('?>' . $config->render());

        $this->assertArrayHasKey('w', $result);
        $this->assertArrayHasKey(0, $result['w']);
        $this->assertArrayHasKey('n', $result['w'][0]);
        $this->assertArrayHasKey(1, $result['w'][0]['n']);
        $this->assertArrayHasKey('e', $result['w'][0]['n'][1]);
        $this->assertArrayHasKey(2, $result['w'][0]['n'][1]['e']);
        $this->assertEquals('cms', $result['w'][0]['n'][1]['e'][2]);
    }

    public function testWriteDotNotationMultiple()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);
        $config->set('w.i.n.t.e.r', 'Winter CMS');
        $config->set('w.i.n.b', 'is');
        $config->set('w.i.n.t.a', 'very');
        $config->set('w.i.n.c.l', 'good');
        $config->set('w.i.n.c.e', 'and');
        $config->set('w.i.n.c.f', 'awesome');
        $config->set('w.i.n.g', 'for');
        $config->set('w.i.2.g', 'development');

        $config->write();

        $contents = file_get_contents($file);

        $expected = "<?php

return [
    'w' => [
        'i' => [
            'n' => [
                't' => [
                    'e' => [
                        'r' => 'Winter CMS',
                    ],
                    'a' => 'very',
                ],
                'b' => 'is',
                'c' => [
                    'l' => 'good',
                    'e' => 'and',
                    'f' => 'awesome',
                ],
                'g' => 'for',
            ],
            2 => [
                'g' => 'development',
            ],
        ],
    ],
];

";

        $this->assertEquals($expected, $contents);

        unlink($file);
    }

    public function testWriteDotDuplicateIntKeys()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);
        $config->set([
            'w.i.n.t.e.r' => 'Winter CMS',
            'w.i.2.g' => 'development',
        ]);
        $config->set('w.i.2.g', 'development');

        $config->write();

        $contents = file_get_contents($file);

        $expected = "<?php

return [
    'w' => [
        'i' => [
            'n' => [
                't' => [
                    'e' => [
                        'r' => 'Winter CMS',
                    ],
                ],
            ],
            2 => [
                'g' => 'development',
            ],
        ],
    ],
];

";

        $this->assertEquals($expected, $contents);

        unlink($file);
    }

    public function testWriteIllegalOffset()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);

        $this->expectException(\Winter\Storm\Exception\SystemException::class);

        $config->set([
            'w.i.n.t.e.r' => 'Winter CMS',
            'w.i.n.t.e.r.2' => 'test',
        ]);
    }

    public function testWriteFunctionCall()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);

        $config->set([
            'key' => $config->function('env', ['KEY_A', true])
        ]);

        $config->set([
            'key2' => new \Winter\Storm\Config\ConfigFunction('nl2br', ['KEY_B', false])
        ]);

        $expected = "<?php

return [
    'key' => env('KEY_A', true),
    'key2' => nl2br('KEY_B', false),
];

";

        $this->assertEquals($expected, $config->render());
    }

    public function testWriteFunctionCallOverwrite()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);

        $config->set([
            'key' => $config->function('env', ['KEY_A', true])
        ]);

        $config->set([
            'key' => new \Winter\Storm\Config\ConfigFunction('nl2br', ['KEY_B', false])
        ]);

        $expected = "<?php

return [
    'key' => nl2br('KEY_B', false),
];

";

        $this->assertEquals($expected, $config->render());
    }

    public function testInsertNull()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);

        $config->set([
            'key' => $config->function('env', ['KEY_A', null]),
            'key2' => null
        ]);

        $expected = "<?php

return [
    'key' => env('KEY_A', null),
    'key2' => null,
];

";

        $this->assertEquals($expected, $config->render());
    }

    public function testSortAsc()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);

        $config->set([
            'b.b' => 'b',
            'b.a' => 'a',
            'a.a.b' => 'b',
            'a.a.a' => 'a',
            'a.c' => 'c',
            'a.b' => 'b',
        ]);

        $config->sort();

        $expected = "<?php

return [
    'a' => [
        'a' => [
            'a' => 'a',
            'b' => 'b',
        ],
        'b' => 'b',
        'c' => 'c',
    ],
    'b' => [
        'a' => 'a',
        'b' => 'b',
    ],
];

";

        $this->assertEquals($expected, $config->render());
    }


    public function testSortDesc()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);

        $config->set([
            'b.a' => 'a',
            'a.a.a' => 'a',
            'a.a.b' => 'b',
            'a.b' => 'b',
            'a.c' => 'c',
            'b.b' => 'b',
        ]);

        $config->sort(ConfigFile::SORT_DESC);

        $expected = "<?php

return [
    'b' => [
        'b' => 'b',
        'a' => 'a',
    ],
    'a' => [
        'c' => 'c',
        'b' => 'b',
        'a' => [
            'b' => 'b',
            'a' => 'a',
        ],
    ],
];

";

        $this->assertEquals($expected, $config->render());
    }

    public function testSortUsort()
    {
        $file = __DIR__ . '/../fixtures/config/empty.php';
        $config = ConfigFile::read($file, true);

        $config->set([
            'a' => 'a',
            'b' => 'b'
        ]);

        $config->sort(function ($a, $b) {
            static $i;
            if (!isset($i)) {
                $i = 1;
            }
            return $i--;
        });

        $expected = "<?php

return [
    'b' => 'b',
    'a' => 'a',
];

";
        $this->assertEquals($expected, $config->render());
    }
}
