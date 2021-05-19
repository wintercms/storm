<?php

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem as Flysystem;
use Winter\Storm\Filesystem\FilesystemAdapter;

class FilesystemAdapterTest extends TestCase
{
    public function test_it_throws_an_exception_when_trying_to_get_a_temporary_url_on_a_local_disk()
    {
        $flysystem = new Flysystem(new Local('/tmp/app'));

        $this->expectException(RuntimeException::class);

        (new FilesystemAdapter($flysystem))->temporaryUrl('test.jpg', \Carbon\Carbon::now()->addMinutes(5));
    }
}
