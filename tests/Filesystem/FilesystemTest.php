<?php

use Winter\Storm\Filesystem\Filesystem;

class FilesystemTest extends TestCase
{
    public function testUnique()
    {
        $filesystem = new Filesystem();

        $this->assertSame('winter_4.cms',  $filesystem->unique('winter.cms', ['winter_1.cms', 'test_5', 'winter_2.cms']));
        $this->assertSame('winter_98.cms', $filesystem->unique('winter.cms', ['winter_97.cms', 'test_5', 'winter_1.cms']));
        $this->assertSame('winter 1.cms',  $filesystem->unique('winter.cms', ['winter_1.cms', 'test_5', 'winter_3.cms'], ' '));
        $this->assertSame('winter_1.cms',  $filesystem->unique('winter.cms', ['test_5']));
    }
}
