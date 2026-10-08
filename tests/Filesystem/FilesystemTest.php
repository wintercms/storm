<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Winter\Storm\Filesystem\Filesystem;

class FilesystemTest extends \Winter\Storm\Tests\TestCase
{
    protected ?Filesystem $filesystem = null;

    public function setUp(): void
    {
        $this->filesystem = new Filesystem();
    }

    #[DataProvider('providePathsForIsAbsolutePath')]
    /** @see Symfony\Component\Filesystem\Tests\FilesystemTest::testIsAbsolutePath */
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

    #[DataProvider('provideSizesForSizeToBytes')]
    public function testSizeToBytes($input, $expectedBytes)
    {
        $result = $this->filesystem->sizeToBytes($input);

        $this->assertEquals($expectedBytes, $result);
    }

    public static function provideSizesForSizeToBytes()
    {
        return [
            ['1 byte', '1'],
            ['1024 bytes', '1024'],
            ['1 KB', '1024'],
            ['1 MB', '1048576'],
            ['2.5 MB', '2621440'],
            ['1 GB', '1073741824'],
            ['1.5 GB', '1610612736'],
            ['2G', '2147483648'],    // PHP shorthand
            ['512M', '536870912'],   // PHP shorthand
            ['256K', '262144'],      // PHP shorthand
        ];
    }

    #[DataProvider('provideBytesForSizeToString')]
    public function testSizeToString($bytes, $expectedString)
    {
        $result = $this->filesystem->sizeToString($bytes);

        $this->assertEquals($expectedString, $result);
    }

    public static function provideBytesForSizeToString()
    {
        return [
            [1, '1 byte'],
            [512, '512 bytes'],
            [1024, '1.00 KB'],
            [1536, '1.50 KB'],
            [1048576, '1.00 MB'],
            [1572864, '1.50 MB'],
            [1073741824, '1.00 GB'],
            [1610612736, '1.50 GB'],
        ];
    }

    #[DataProvider('provideInvalidSizesForSizeToBytes')]
    public function testSizeToBytesInvalidInput($input)
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->filesystem->sizeToBytes($input);
    }

    public static function provideInvalidSizesForSizeToBytes()
    {
        return [
            ['-1G'],               // Negative value
            ['abc MB'],            // Invalid numeric value
            ['1.5 XB'],            // Unsupported unit
            ['gigabyte'],          // Unsupported unit without value
            [''],                  // Empty string
            ['1.23.45 MB'],        // Malformed input
        ];
    }
}
