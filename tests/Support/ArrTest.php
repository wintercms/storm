<?php

use Winter\Storm\Support\Arr;

class ArrTest extends TestCase
{
    public function testMoveKeyToIndex()
    {
        $array = [
            'one' => 'a',
            'two' => 'b',
            'three' => 'c',
        ];

        // 0 based index means that index 1 is the second element
        $this->assertSame([
            'one' => 'a',
            'three' => 'c',
            'two' => 'b',
        ], Arr::moveKeyToIndex($array, 'three', 1));

        // 0 index inserts at start
        $this->assertSame([
            'two' => 'b',
            'one' => 'a',
            'three' => 'c',
        ], Arr::moveKeyToIndex($array, 'two', 0));

        // Index out of range inserts at end
        $this->assertSame([
            'one' => 'a',
            'three' => 'c',
            'two' => 'b',
        ], Arr::moveKeyToIndex($array, 'two', 10));

        // Negative index inserting as first element
        $this->assertSame([
            'two' => 'b',
            'one' => 'a',
            'three' => 'c',
        ], Arr::moveKeyToIndex($array, 'two', -10));

        // Elements with null values are correctly able to be sorted
        $nullValueArray = $array;
        $nullValueArray['two'] = null;
        $this->assertSame([
            'one' => 'a',
            'three' => 'c',
            'two' => null,
        ], Arr::moveKeyToIndex($nullValueArray, 'two', 2));
    }

    public function testArrClass()
    {
        $array = [
            'test' => 'value',
            'test2.child1' => 'value2',
            'test2.child2.grandchild1' => 'value3',
            'test2.child3.0.name' => 'Ben',
            'test2.child3.0.surname' => 'Thomson',
            'test2.child3.1.name' => 'John',
            'test2.child3.1.surname' => 'Doe',
        ];

        $this->assertEquals([
            'test' => 'value',
            'test2' => [
                'child1' => 'value2',
                'child2' => [
                    'grandchild1' => 'value3',
                ],
                'child3' => [
                    [
                        'name' => 'Ben',
                        'surname' => 'Thomson',
                    ],
                    [
                        'name' => 'John',
                        'surname' => 'Doe'
                    ]
                ]
            ]
        ], Arr::undot($array));
    }

    public function testHelper()
    {
        $array = [
            'test' => 'value',
            'test2.child1' => 'value2',
            'test2.child2.grandchild1' => 'value3',
            'test2.child3.0.name' => 'Ben',
            'test2.child3.0.surname' => 'Thomson',
            'test2.child3.1.name' => 'John',
            'test2.child3.1.surname' => 'Doe',
        ];

        $this->assertEquals([
            'test' => 'value',
            'test2' => [
                'child1' => 'value2',
                'child2' => [
                    'grandchild1' => 'value3',
                ],
                'child3' => [
                    [
                        'name' => 'Ben',
                        'surname' => 'Thomson',
                    ],
                    [
                        'name' => 'John',
                        'surname' => 'Doe'
                    ]
                ]
            ]
        ], array_undot($array));
    }
}
