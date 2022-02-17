<?php

use Winter\Storm\Parse\PHP\ArrayFile;

class ArrayFileTest extends TestCase
{
    public function testReadFile()
    {
        $filePath = __DIR__ . '/../fixtures/parse/sample-array-file.php';

        $arrayFile = ArrayFile::open($filePath);

        $this->assertInstanceOf(ArrayFile::class, $arrayFile);

        $ast = $arrayFile->getAst();

        $this->assertTrue(isset($ast[0]->expr->items[0]->key->value));
        $this->assertEquals('debug', $ast[0]->expr->items[0]->key->value);
    }

    public function testWriteFile()
    {
        $filePath = __DIR__ . '/../fixtures/parse/sample-array-file.php';
        $tmpFile = __DIR__ . '/../fixtures/parse/temp-array-file.php';

        $arrayFile = ArrayFile::open($filePath);
        $arrayFile->write($tmpFile);

        $result = include $tmpFile;
        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('sqlite', $result['connections']);
        $this->assertArrayHasKey('driver', $result['connections']['sqlite']);
        $this->assertEquals('sqlite', $result['connections']['sqlite']['driver']);

        unlink($tmpFile);
    }

    public function testWriteFileWithUpdates()
    {
        $filePath = __DIR__ . '/../fixtures/parse/sample-array-file.php';
        $tmpFile = __DIR__ . '/../fixtures/parse/temp-array-file.php';

        $arrayFile = ArrayFile::open($filePath);
        $arrayFile->set('connections.sqlite.driver', 'winter');
        $arrayFile->write($tmpFile);

        $result = include $tmpFile;
        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('sqlite', $result['connections']);
        $this->assertArrayHasKey('driver', $result['connections']['sqlite']);
        $this->assertEquals('winter', $result['connections']['sqlite']['driver']);

        unlink($tmpFile);
    }

    public function testWriteFileWithUpdatesArray()
    {
        $filePath = __DIR__ . '/../fixtures/parse/sample-array-file.php';
        $tmpFile = __DIR__ . '/../fixtures/parse/temp-array-file.php';

        $arrayFile = ArrayFile::open($filePath);
        $arrayFile->set([
            'connections.sqlite.driver' => 'winter',
            'connections.sqlite.prefix' => 'test',
        ]);
        $arrayFile->write($tmpFile);

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
        $filePath = __DIR__ . '/../fixtures/parse/env-config.php';
        $tmpFile = __DIR__ . '/../fixtures/parse/temp-array-file.php';

        $arrayFile = ArrayFile::open($filePath);
        $arrayFile->write($tmpFile);

        $result = include $tmpFile;

        $this->assertArrayHasKey('sample', $result);
        $this->assertArrayHasKey('value', $result['sample']);
        $this->assertArrayHasKey('no_default', $result['sample']);
        $this->assertEquals('default', $result['sample']['value']);
        $this->assertNull($result['sample']['no_default']);

        $arrayFile->set([
            'sample.value' => 'winter',
            'sample.no_default' => 'test',
        ]);
        $arrayFile->write($tmpFile);

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
        $arrayFile = ArrayFile::open(__DIR__ . '/../fixtures/parse/sample-array-file.php');
        $result = eval('?>' . $arrayFile->render());

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('http://localhost', $result['url']);

        $arrayFile = ArrayFile::open(__DIR__ . '/../fixtures/parse/sample-array-file.php');
        $arrayFile->set('url', false);
        $result = eval('?>' . $arrayFile->render());

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertFalse($result['url']);

        $arrayFile = ArrayFile::open(__DIR__ . '/../fixtures/parse/sample-array-file.php');
        $arrayFile->set('url', 1234);
        $result = eval('?>' . $arrayFile->render());

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertIsInt($result['url']);
    }

    public function testRender()
    {
        /*
         * Rewrite a single level string
         */
        $arrayFile = ArrayFile::open(__DIR__ . '/../fixtures/parse/sample-array-file.php');
        $arrayFile->set('url', 'https://wintercms.com');
        $result = eval('?>' . $arrayFile->render());

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('https://wintercms.com', $result['url']);

        /*
         * Rewrite a second level string
         */
        $arrayFile = ArrayFile::open(__DIR__ . '/../fixtures/parse/sample-array-file.php');
        $arrayFile->set('memcached.host', '69.69.69.69');
        $result = eval('?>' . $arrayFile->render());

        $this->assertArrayHasKey('memcached', $result);
        $this->assertArrayHasKey('host', $result['memcached']);
        $this->assertEquals('69.69.69.69', $result['memcached']['host']);

        /*
         * Rewrite a third level string
         */
        $arrayFile = ArrayFile::open(__DIR__ . '/../fixtures/parse/sample-array-file.php');
        $arrayFile->set('connections.mysql.host', '127.0.0.1');
        $result = eval('?>' . $arrayFile->render());

        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('mysql', $result['connections']);
        $this->assertArrayHasKey('host', $result['connections']['mysql']);
        $this->assertEquals('127.0.0.1', $result['connections']['mysql']['host']);

        /*un-
         * Test alternative quoting
         */
        $arrayFile = ArrayFile::open(__DIR__ . '/../fixtures/parse/sample-array-file.php');
        $arrayFile->set('timezone', 'The Fifth Dimension')
            ->set('timezoneAgain', 'The "Sixth" Dimension');
        $result = eval('?>' . $arrayFile->render());

        $this->assertArrayHasKey('timezone', $result);
        $this->assertArrayHasKey('timezoneAgain', $result);
        $this->assertEquals('The Fifth Dimension', $result['timezone']);
        $this->assertEquals('The "Sixth" Dimension', $result['timezoneAgain']);

        /*
         * Rewrite a boolean
         */
        $arrayFile = ArrayFile::open(__DIR__ . '/../fixtures/parse/sample-array-file.php');
        $arrayFile->set('debug', false)
            ->set('debugAgain', true)
            ->set('bullyIan', true)
            ->set('booLeeIan', false)
            ->set('memcached.weight', false)
            ->set('connections.pgsql.password', true);

        $result = eval('?>' . $arrayFile->render());

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
        $arrayFile = ArrayFile::open(__DIR__ . '/../fixtures/parse/sample-array-file.php');
        $arrayFile->set('aNumber', 69);
        $result = eval('?>' . $arrayFile->render());

        $this->assertArrayHasKey('aNumber', $result);
        $this->assertEquals(69, $result['aNumber']);
    }

    public function testReadCreateFile()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';

        $this->assertFalse(file_exists($file));

        $arrayFile = ArrayFile::open($file);

        $this->assertInstanceOf(ArrayFile::class, $arrayFile);

        $arrayFile->write();

        $this->assertTrue(file_exists($file));
        $this->assertEquals(sprintf('<?php%1$s%1$sreturn [];%1$s', "\n"), file_get_contents($file));

        unlink($file);
    }

    public function testWriteDotNotation()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);
        $arrayFile->set('w.i.n.t.e.r', 'cms');

        $result = eval('?>' . $arrayFile->render());

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
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);
        $arrayFile->set('w.0.n.1.e.2', 'cms');

        $result = eval('?>' . $arrayFile->render());

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
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);
        $arrayFile->set('w.i.n.t.e.r', 'Winter CMS');
        $arrayFile->set('w.i.n.b', 'is');
        $arrayFile->set('w.i.n.t.a', 'very');
        $arrayFile->set('w.i.n.c.l', 'good');
        $arrayFile->set('w.i.n.c.e', 'and');
        $arrayFile->set('w.i.n.c.f', 'awesome');
        $arrayFile->set('w.i.n.g', 'for');
        $arrayFile->set('w.i.2.g', 'development');

        $arrayFile->write();

        $contents = file_get_contents($file);

        $expected = <<<PHP
<?php

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

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $contents);

        unlink($file);
    }

    public function testWriteDotDuplicateIntKeys()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);
        $arrayFile->set([
            'w.i.n.t.e.r' => 'Winter CMS',
            'w.i.2.g' => 'development',
        ]);
        $arrayFile->set('w.i.2.g', 'development');

        $arrayFile->write();

        $contents = file_get_contents($file);

        $expected = <<<PHP
<?php

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

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $contents);

        unlink($file);
    }

    public function testWriteIllegalOffset()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $this->expectException(\Winter\Storm\Exception\SystemException::class);

        $arrayFile->set([
            'w.i.n.t.e.r' => 'Winter CMS',
            'w.i.n.t.e.r.2' => 'test',
        ]);
    }

    public function testThrowExceptionIfMissing()
    {
        $file = __DIR__ . '/../fixtures/parse/missing.php';

        $this->expectException(\InvalidArgumentException::class);

        $arrayFile = ArrayFile::open($file, true);
    }

    public function testSetArray()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $arrayFile->set([
            'w' => [
                'i' => 'n',
                't' => [
                    'e',
                    'r'
                ]
            ]
        ]);

        $expected = <<<PHP
<?php

return [
    'w' => [
        'i' => 'n',
        't' => [
            'e',
            'r',
        ],
    ],
];

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $arrayFile->render());
    }

    public function testSetNumericArray()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $arrayFile->set([
            'winter' => [
                1 => 'a',
                2 => 'b',
            ],
            'cms' => [
                0 => 'a',
                1 => 'b'
            ]
        ]);

        $expected = <<<PHP
<?php

return [
    'winter' => [
        1 => 'a',
        2 => 'b',
    ],
    'cms' => [
        'a',
        'b',
    ],
];

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $arrayFile->render());
    }

    public function testWriteConstCall()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $arrayFile->set([
            'curl_port' => $arrayFile->constant('CURLOPT_PORT')
        ]);

        $arrayFile->set([
            'curl_return' => new \Winter\Storm\Parse\PHP\PHPConstant('CURLOPT_RETURNTRANSFER')
        ]);

        $expected = <<<PHP
<?php

return [
    'curl_port' => CURLOPT_PORT,
    'curl_return' => CURLOPT_RETURNTRANSFER,
];

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $arrayFile->render());
    }

    public function testWriteArrayFunctionsAndConstCall()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $arrayFile->set([
            'path.to.config' => [
                'test' => $arrayFile->function('env', ['TEST_KEY', 'default']),
                'details' => [
                    'test1',
                    'test2',
                    'additional' => [
                        $arrayFile->constant('\Winter\Storm\Parse\PHP\ArrayFile::SORT_ASC'),
                        $arrayFile->constant('\Winter\Storm\Parse\PHP\ArrayFile::SORT_DESC')
                    ]
                ]
            ]
        ]);

        $expected = <<<PHP
<?php

return [
    'path' => [
        'to' => [
            'config' => [
                'test' => env('TEST_KEY', 'default'),
                'details' => [
                    'test1',
                    'test2',
                    'additional' => [
                        \Winter\Storm\Parse\PHP\ArrayFile::SORT_ASC,
                        \Winter\Storm\Parse\PHP\ArrayFile::SORT_DESC,
                    ],
                ],
            ],
        ],
    ],
];

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $arrayFile->render());
    }

    public function testWriteFunctionCall()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $arrayFile->set([
            'key' => $arrayFile->function('env', ['KEY_A', true])
        ]);

        $arrayFile->set([
            'key2' => new \Winter\Storm\Parse\PHP\PHPFunction('nl2br', ['KEY_B', false])
        ]);

        $expected = <<<PHP
<?php

return [
    'key' => env('KEY_A', true),
    'key2' => nl2br('KEY_B', false),
];

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $arrayFile->render());
    }

    public function testWriteFunctionCallOverwrite()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $arrayFile->set([
            'key' => $arrayFile->function('env', ['KEY_A', true])
        ]);

        $arrayFile->set([
            'key' => new \Winter\Storm\Parse\PHP\PHPFunction('nl2br', ['KEY_B', false])
        ]);

        $expected = <<<PHP
<?php

return [
    'key' => nl2br('KEY_B', false),
];

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $arrayFile->render());
    }

    public function testInsertNull()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $arrayFile->set([
            'key' => $arrayFile->function('env', ['KEY_A', null]),
            'key2' => null
        ]);

        $expected = <<<PHP
<?php

return [
    'key' => env('KEY_A', null),
    'key2' => null,
];

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $arrayFile->render());
    }

    public function testSortAsc()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $arrayFile->set([
            'b.b' => 'b',
            'b.a' => 'a',
            'a.a.b' => 'b',
            'a.a.a' => 'a',
            'a.c' => 'c',
            'a.b' => 'b',
        ]);

        $arrayFile->sort();

        $expected = <<<PHP
<?php

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

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $arrayFile->render());
    }


    public function testSortDesc()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $arrayFile->set([
            'b.a' => 'a',
            'a.a.a' => 'a',
            'a.a.b' => 'b',
            'a.b' => 'b',
            'a.c' => 'c',
            'b.b' => 'b',
        ]);

        $arrayFile->sort(ArrayFile::SORT_DESC);

        $expected = <<<PHP
<?php

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

PHP;

        $this->assertEquals(str_replace("\r", '', $expected), $arrayFile->render());
    }

    public function testSortUsort()
    {
        $file = __DIR__ . '/../fixtures/parse/empty.php';
        $arrayFile = ArrayFile::open($file);

        $arrayFile->set([
            'a' => 'a',
            'b' => 'b'
        ]);

        $arrayFile->sort(function ($a, $b) {
            static $i;
            if (!isset($i)) {
                $i = 1;
            }
            return $i--;
        });

        $expected = <<<PHP
<?php

return [
    'b' => 'b',
    'a' => 'a',
];

PHP;
        $this->assertEquals(str_replace("\r", '', $expected), $arrayFile->render());
    }
}
