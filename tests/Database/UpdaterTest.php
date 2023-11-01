<?php

use Winter\Storm\Database\Updater;

class UpdaterTest extends TestCase
{
    protected Updater $updater;

    public function setUp(): void
    {
        include_once __DIR__.'/../fixtures/database/SampleClass.php';

        $this->updater = new Updater();
    }

    public function testClassNameGetsParsedCorrectly()
    {
        $reflector = new ReflectionClass(TestPlugin\SampleClass::class);
        $filePath = $reflector->getFileName();

        $classFullName = $this->updater->getClassFromFile($filePath);

        $this->assertEquals(TestPlugin\SampleClass::class, $classFullName);
    }
}
