<?php

use Winter\Storm\Database\Factories\Factory;

class FactoryTest extends TestCase
{
    public function testResolveFactoryName()
    {
        $factoryClass = Factory::resolveFactoryName('Module\Models\TestModel');
        $this->assertEquals($factoryClass, 'Module\Database\Factories\TestModelFactory');

        $factoryClass = Factory::resolveFactoryName('Plugin\Author\Models\TestModel');
        $this->assertEquals($factoryClass, 'Plugin\Author\Database\Factories\TestModelFactory');

        $factoryClass = Factory::resolveFactoryName('Models\TestModel');
        $this->assertEquals($factoryClass, 'Database\Factories\TestModelFactory');

        $factoryClass = Factory::resolveFactoryName('TestModel');
        $this->assertEquals($factoryClass, 'Database\Factories\TestModelFactory');
    }
}
