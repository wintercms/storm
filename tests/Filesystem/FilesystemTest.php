<?php

use Winter\Storm\Filesystem\Filesystem;

/**
 *
 */
class FilesystemTest extends TestCase
{
    protected ?Filesystem $filesystem = null;

    public function setUp(): void
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @dataProvider providePathsForIsAbsolutePath
     * @see Symfony\Component\Filesystem\Tests\FilesystemTest::testIsAbsolutePath
     */
    public function testIsAbsolutePath($path, $expectedResult)
    {
        $result = $this->filesystem->isAbsolutePath($path);

        $this->assertEquals($expectedResult, $result);
    }

    public static function providePathsForIsAbsolutePath()
    {
        return [
            ['/var/lib', true],
            ['c:\\\\var\\lib', true],
            ['\\var\\lib', true],
            ['var/lib', false],
            ['../var/lib', false],
            ['', false],
        ];
    }
}
