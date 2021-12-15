<?php

use Winter\Storm\Parse\Processor\YamlProcessor;
use Symfony\Component\Yaml\Exception\ParseException;
use Winter\Storm\Parse\Processor\Contracts\YamlProcessor;
use Winter\Storm\Parse\Processor\Symfony3Processor;
use Winter\Storm\Parse\Yaml as YamlParser;

class YamlTest extends TestCase
{
    public function testParseWithoutProcessor()
    {
        $parser = new YamlParser;
        $yaml = $parser->parse(file_get_contents(dirname(__DIR__) . '/fixtures/yaml/test.yaml'));

        $this->assertEquals([
            'test' => [
                'test1' => 'Test 1 Value',
                'test2' => [
                    'test21' => 'Test 2 Value',
                ],
                'test3' => [
                    'Test 3 Value 1',
                    'Test 3 Value 2',
                    'Test 3 Value 3',
                ],
            ],
        ], $yaml);
    }

    public function testParseWithPreProcessor()
    {
        $parser = new YamlParser;
        $parser->setProcessor(new UppercaseYamlProcessor);
        $yaml = $parser->parse(file_get_contents(dirname(__DIR__) . '/fixtures/yaml/test.yaml'));
        $parser->removeProcessor();

        $this->assertEquals([
            'TEST' => [
                'TEST1' => 'TEST 1 VALUE',
                'TEST2' => [
                    'TEST21' => 'TEST 2 VALUE',
                ],
                'TEST3' => [
                    'TEST 3 VALUE 1',
                    'TEST 3 VALUE 2',
                    'TEST 3 VALUE 3',
                ],
            ],
        ], $yaml);
    }

    public function testParseWithPreProcessorTemporarily()
    {
        $parser = new YamlParser;
        $yaml = $parser->withProcessor(new UppercaseYamlProcessor, function ($yaml) {
            return $yaml->parse(file_get_contents(dirname(__DIR__) . '/fixtures/yaml/test.yaml'));
        });

        $this->assertEquals([
            'TEST' => [
                'TEST1' => 'TEST 1 VALUE',
                'TEST2' => [
                    'TEST21' => 'TEST 2 VALUE',
                ],
                'TEST3' => [
                    'TEST 3 VALUE 1',
                    'TEST 3 VALUE 2',
                    'TEST 3 VALUE 3',
                ],
            ],
        ], $yaml);
    }

    public function testParseWithPostProcessor()
    {
        $parser = new YamlParser;
        $parser->setProcessor(new ObjectYamlProcessor);
        $yaml = $parser->parse(file_get_contents(dirname(__DIR__) . '/fixtures/yaml/test.yaml'));
        $parser->removeProcessor();

        $this->assertIsObject($yaml);
        $this->assertEquals([
            'test1' => 'Test 1 Value',
            'test2' => [
                'test21' => 'Test 2 Value',
            ],
            'test3' => [
                'Test 3 Value 1',
                'Test 3 Value 2',
                'Test 3 Value 3',
            ],
        ], $yaml->test);
    }

    public function testRenderWithoutProcessor()
    {
        $parser = new YamlParser;

        $yaml = $parser->render([
            '1.0.0' => [
                'First version',
                'some_update_file.php',
            ],
            '1.0.1' => [
                'Second version',
            ],
            'test' => [
                'String-based key',
            ],
            'test two' => [
                'String-based key with a space',
            ],
        ]);

        $this->assertIsString($yaml);
        $this->assertEquals(
            "1.0.0:\n" .
            "    - 'First version'\n" .
            "    - some_update_file.php\n" .
            "1.0.1:\n" .
            "    - 'Second version'\n" .
            "test:\n" .
            "    - 'String-based key'\n" .
            "'test two':\n" .
            "    - 'String-based key with a space'\n",
            $yaml
        );
    }

    public function testRenderWithPreProcessor()
    {
        $parser = new YamlParser;

        $parser->setProcessor(new UppercaseKeysProcessor);
        $yaml = $parser->render([
            '1.0.0' => [
                'First version',
                'some_update_file.php',
            ],
            '1.0.1' => [
                'Second version',
            ],
            'test' => [
                'String-based key',
            ],
            'test two' => [
                'String-based key with a space',
            ],
        ]);
        $parser->removeProcessor();

        $this->assertIsString($yaml);
        $this->assertEquals(
            "1.0.0:\n" .
            "    - 'First version'\n" .
            "    - some_update_file.php\n" .
            "1.0.1:\n" .
            "    - 'Second version'\n" .
            "TEST:\n" .
            "    - 'String-based key'\n" .
            "'TEST TWO':\n" .
            "    - 'String-based key with a space'\n",
            $yaml
        );
    }

    public function testRenderWithPreAndPostProcessor()
    {
        $parser = new YamlParser;

        $parser->setProcessor(new QuotedUpperKeysProcessor);
        $yaml = $parser->render([
            '1.0.0' => [
                'First version',
                'some_update_file.php',
            ],
            '1.0.1' => [
                'Second version',
            ],
            'test' => [
                'String-based key',
            ],
            'test two' => [
                'String-based key with a space',
            ],
        ]);
        $parser->removeProcessor();

        $this->assertIsString($yaml);
        $this->assertEquals(
            "'1.0.0':\n" .
            "    - 'First version'\n" .
            "    - some_update_file.php\n" .
            "'1.0.1':\n" .
            "    - 'Second version'\n" .
            "'TEST':\n" .
            "    - 'String-based key'\n" .
            "'TEST TWO':\n" .
            "    - 'String-based key with a space'\n",
            $yaml
        );
    }
    
    public function testSymfony3YamlFile()
    {
        // This YAML file should not be parseable by default
        $this->expectException(ParseException::class);

        $parser = new YamlParser;
        $parser->parse(file_get_contents(dirname(__DIR__) . '/fixtures/yaml/symfony3.yaml'));
    }

    public function testSymfony3YamlFileWithProcessor()
    {
        $parser = new YamlParser;
        $parser->setProcessor(new Symfony3Processor);
        $yaml = $parser->parse(file_get_contents(dirname(__DIR__) . '/fixtures/yaml/symfony3.yaml'));

        $this->assertEquals([
            'form' => [
                'fields' => [
                    'testField' => [
                        'type' => 'text',
                        'label' => 'Test field',
                    ],
                    'testSelect' => [
                        'type' => 'select',
                        'label' => 'Do you rock the casbah?',
                        'options' => [
                            '0' => 'Nope',
                            '1' => 'ROCK THE CASBAH!',
                            '2' => 2,
                        ],
                    ],
                    'testSelectTwo' => [
                        'type' => 'select',
                        'label' => 'Which decade of songs did you like?',
                        'options' => [
                            '1960s',
                            '1970s',
                            '1980s',
                            '1990s',
                            '2000s',
                            '2010s',
                            '2020s',
                        ],
                    ],
                    'testBoolean' => [
                        'type' => 'select',
                        'label' => 'Is the sky blue?',
                        'options' => [
                            'true' => true,
                            'false' => false,
                        ],
                    ],
                ],
            ],
        ], $yaml);
    }
}

/**
 * Test parse pre-processor
 */
class UppercaseYamlProcessor extends YamlProcessor
{
    public function preprocess($text)
    {
        return strtoupper($text);
    }
}

/**
 * Test parse post-processor
 */
class ObjectYamlProcessor extends YamlProcessor
{
    public function process($parsed)
    {
        return (object) $parsed;
    }
}

/**
 * Test render pre-processor
 */
class UppercaseKeysProcessor extends YamlProcessor
{
    public function prerender($data)
    {
        $processed = [];

        foreach ($data as $key => $value) {
            $processed[strtoupper($key)] = $value;
        }

        return $processed;
    }
}

/**
 * Test render pre-and-post-processor
 */
class QuotedUpperKeysProcessor extends YamlProcessor
{
    public function prerender($data)
    {
        $processed = [];

        foreach ($data as $key => $value) {
            $processed[strtoupper($key)] = $value;
        }

        return $processed;
    }

    public function render($yaml)
    {
        return preg_replace_callback('/^\s*([\'"]{0}[^\'"\n\r:]+[\'"]{0})\s*:\s*$/m', function ($matches) {
            return "'" . trim($matches[1]) . "':";
        }, $yaml);
    }
}
