<?php

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Filesystem as Flysystem;
use Illuminate\Filesystem\FilesystemAdapter;

class FilesystemAdapterTest extends TestCase
{
    public function test_it_throws_an_exception_when_trying_to_get_a_temporary_url_on_a_local_disk()
    {
        $this->expectException(RuntimeException::class);

        $adapter = new LocalFilesystemAdapter('/tmp/app');
        (new FilesystemAdapter(new Flysystem($adapter), $adapter))
            ->temporaryUrl('test.jpg', \Carbon\Carbon::now()->addMinutes(5));
    }
}
