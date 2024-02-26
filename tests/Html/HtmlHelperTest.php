<?php

use Winter\Storm\Html\Helper as HtmlHelper;

class HtmlHelperTest extends TestCase
{
    public function testNameToId()
    {
        $result = HtmlHelper::nameToId('field');
        $this->assertEquals('field', $result);

        $result = HtmlHelper::nameToId('field[key1]');
        $this->assertEquals('field-key1', $result);

        $result = HtmlHelper::nameToId('field[][key1]');
        $this->assertEquals('field--key1', $result);

        $result = HtmlHelper::nameToId('field[key1][key2][key3]');
        $this->assertEquals('field-key1-key2-key3', $result);
    }

    public function testNameToArray()
    {
        $result = HtmlHelper::nameToArray('field');
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        $this->assertTrue(in_array('field', $result));

        $result = HtmlHelper::nameToArray('field[key1]');
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));

        $result = HtmlHelper::nameToArray('field[][key1]');
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));

        $result = HtmlHelper::nameToArray('field[key1][key2][key3]');
        $this->assertIsArray($result);
        $this->assertEquals(4, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));
        $this->assertTrue(in_array('key2', $result));
        $this->assertTrue(in_array('key3', $result));
    }

    public function testReduceNameHierarchy()
    {
        $allTests = [
            "1" => [
                "" => "",
                "Form" => "",
                "Form[nestedForm]" => "Form",
                "Form[repeater][0]" => "Form",
                "Form[repeater][44]" => "Form",
            ],
            "2" => [
                "" => "",
                "Form" => "",
                "Form[nestedForm]" => "",
                "Form[repeater][0]" => "",
                "Form[repeater][44]" => "",
                "Form[nestedForm][secondNestedForm]" => "Form",
                "Form[repeater][0][nestedForm]" => "Form",
                "Form[repeater][0][nestedRepeater][1]" => "Form",
                "Form[repeater][0][nestedForm][nestedRepeater][1]" => "Form[repeater][0]",
            ],
        ];
        foreach ($allTests as $level => $tests) {
            foreach ($tests as $test => $expectedResult) {
                $result = HtmlHelper::reduceNameHierarchy($test, intval($level));
                $this->assertEquals($expectedResult, $result);
            }
        }
    }
}
