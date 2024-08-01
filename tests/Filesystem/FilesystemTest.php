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

    public function testUnique()
    {
        $this->assertSame('winter_4.cms', $this->filesystem->unique('winter.cms', ['winter_1.cms', 'test_5', 'winter_3.cms']));
        $this->assertSame('winter_98.cms', $this->filesystem->unique('winter.cms', ['winter_97.cms', 'test_5', 'winter_1.cms']));
        $this->assertSame('winter 1.cms', $this->filesystem->unique('winter.cms', ['winter_1.cms', 'test_5', 'winter_3.cms'], ' '));
        $this->assertSame('winter_1.cms', $this->filesystem->unique('winter.cms', ['test_5']));
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

    public function providePathsForIsAbsolutePath()
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
