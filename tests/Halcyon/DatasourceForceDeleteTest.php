<?php

use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Halcyon\Datasource\Datasource;
use Winter\Storm\Halcyon\Datasource\FileDatasource;

class DatasourceForceDeleteTest extends \Winter\Storm\Tests\TestCase
{
    protected function getForceDeleting(Datasource $datasource): bool
    {
        return (new ReflectionProperty(Datasource::class, 'forceDeleting'))->getValue($datasource);
    }

    public function testForceDeleteResetsTheFlagWhenDeleteThrows()
    {
        $datasource = new class ('/tmp', new Filesystem) extends FileDatasource
        {
            public function delete(string $dirName, string $fileName, string $extension): bool
            {
                throw new RuntimeException('delete failed');
            }
        };

        $caught = null;

        try {
            $datasource->forceDelete('pages', 'index', 'htm');
        }
        catch (RuntimeException $ex) {
            $caught = $ex;
        }

        // Asserted outside the catch: PHPUnit's own assertion failures extend RuntimeException,
        // so a fail() inside the try would be swallowed by the catch above.
        $this->assertInstanceOf(RuntimeException::class, $caught, 'forceDelete() must propagate the failure');
        $this->assertSame('delete failed', $caught->getMessage());

        $this->assertFalse(
            $this->getForceDeleting($datasource),
            'A failed force delete must not leave the datasource in force-deleting mode, or every '
            . 'later ordinary delete on this instance becomes a hard delete.'
        );
    }

    public function testForceDeleteReturnsTheDeleteResultAndResetsTheFlag()
    {
        $datasource = new class ('/tmp', new Filesystem) extends FileDatasource
        {
            /**
             * @var bool The flag value observed while delete() ran.
             */
            public $flagDuringDelete = false;

            public function delete(string $dirName, string $fileName, string $extension): bool
            {
                $this->flagDuringDelete = $this->forceDeleting;

                return true;
            }
        };

        $this->assertTrue($datasource->forceDelete('pages', 'index', 'htm'));
        $this->assertTrue($datasource->flagDuringDelete, 'delete() should run in force-deleting mode');
        $this->assertFalse($this->getForceDeleting($datasource));
    }
}
