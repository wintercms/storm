<?php

use Winter\Storm\Parse\Processor\Contracts\YamlProcessor;
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
}

/**
 * Test pre-processor
 */
class UppercaseYamlProcessor implements YamlProcessor
{
    public function preprocess($text)
    {
        return strtoupper($text);
    }

    public function process($parsed)
    {
        return $parsed;
    }
}

/**
 * Test post-processor
 */
class ObjectYamlProcessor implements YamlProcessor
{
    public function preprocess($text)
    {
        return $text;
    }

    public function process($parsed)
    {
        return (object) $parsed;
    }
}
